<?php

namespace App\Http\Controllers\Api;

use App\Events\RoomUpdated;
use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\GameEngine;
use App\Games\Xiangqi\GameState;
use App\Games\Xiangqi\GameStatus;
use App\Games\Xiangqi\GameStatusResolver;
use App\Games\Xiangqi\IllegalMoveException;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    /** Selectable clock presets, in seconds per side (matches "5 / 10 / 15 min"). */
    private const TIME_CONTROLS = [300, 600, 900];

    public function index(): JsonResponse
    {
        $rooms = Room::query()
            ->where('status', 'waiting')
            ->with('host:id,name')
            ->latest()
            ->limit(50)
            ->get(['id', 'code', 'stake', 'host_id', 'time_control', 'created_at']);

        return response()->json(['rooms' => $rooms]);
    }

    public function mine(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $rooms = Room::query()
            ->where('host_id', $userId)
            ->orWhere('guest_id', $userId)
            ->with(['host:id,name', 'guest:id,name'])
            ->latest()
            ->limit(50)
            ->get(['id', 'code', 'stake', 'status', 'result', 'host_id', 'guest_id', 'winner_id', 'started_at', 'ended_at', 'created_at']);

        return response()->json(['rooms' => $rooms]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'time_control' => ['nullable', 'integer', 'in:'.implode(',', self::TIME_CONTROLS)],
            'stake' => ['nullable', 'integer', 'min:0'],
        ]);

        $stake = $data['stake'] ?? 0;
        $hostId = $request->user()->id;
        $minStake = config('points.min_stake');

        if ($stake > 0 && $stake < $minStake) {
            return response()->json(['message' => "The minimum stake is {$minStake} points."], 422);
        }

        // Row-locked (when staking) so two concurrent create-room requests
        // from the same host can never both pass the balance check before
        // either decrements - without this, firing the request twice in
        // parallel can escrow more points than the host actually has.
        $room = DB::transaction(function () use ($hostId, $stake, $data) {
            $host = $stake > 0 ? User::whereKey($hostId)->lockForUpdate()->first() : null;

            if ($host && $host->points < $stake) {
                return null;
            }

            $room = Room::create([
                'code' => (string) random_int(100000, 999999),
                'stake' => $stake,
                'host_id' => $hostId,
                'status' => 'waiting',
                'turn' => 'red',
                'move_history' => [],
                'time_control' => $data['time_control'] ?? null,
            ]);

            // Escrowed immediately so a host can't stake points they've
            // already spent elsewhere while the room sits open waiting.
            if ($host) {
                $host->decrement('points', $stake);
            }

            return $room;
        });

        if (! $room) {
            return response()->json(['message' => 'You do not have enough points for this stake.'], 422);
        }

        return response()->json($this->present($room->fresh('host', 'guest')), 201);
    }

    /**
     * Lets the host back out of a room nobody has joined yet, refunding
     * whatever stake was escrowed at creation.
     */
    public function cancel(Request $request, Room $room): JsonResponse
    {
        if ($room->host_id !== $request->user()->id) {
            return response()->json(['message' => 'Only the host can cancel this room.'], 403);
        }

        if ($room->status !== 'waiting') {
            return response()->json(['message' => 'This room can no longer be cancelled.'], 422);
        }

        if ($room->stake > 0) {
            $room->host->increment('points', $room->stake);
        }

        $room->update(['status' => 'abandoned', 'result' => 'abandoned']);

        return response()->json($this->present($room->fresh('host', 'guest')));
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json($this->present($room->load('host', 'guest')));
    }

    /**
     * Reconstructs the board after every ply by replaying the room's stored
     * move history from the starting position, so a finished match can be
     * stepped through move-by-move after the fact.
     */
    public function replay(Room $room): JsonResponse
    {
        $board = Board::initial();
        $boards = [$board->toArray()];

        foreach ($room->move_history ?? [] as $moveData) {
            $from = Position::fromArray($moveData['from']);
            $to = Position::fromArray($moveData['to']);

            $board = $board->clone();
            $board->set($to, $board->get($from));
            $board->set($from, null);

            $boards[] = $board->toArray();
        }

        return response()->json(['boards' => $boards]);
    }

    public function join(Request $request, Room $room): JsonResponse
    {
        if ($room->host_id === $request->user()->id) {
            return response()->json(['message' => 'You cannot join your own room.'], 422);
        }

        $guestId = $request->user()->id;

        // Row-locked (room + guest) so two people joining the same room at
        // once - or the same guest firing the request twice - can't both
        // pass the "waiting"/balance checks before either update commits.
        // Without this, a second joiner's update could silently overwrite
        // the first joiner's guest_id after both already escrowed points.
        $outcome = DB::transaction(function () use ($room, $guestId) {
            $freshRoom = Room::whereKey($room->id)->lockForUpdate()->first();

            if ($freshRoom->status !== 'waiting') {
                return 'taken';
            }

            if ($freshRoom->stake > 0) {
                $guest = User::whereKey($guestId)->lockForUpdate()->first();

                if ($guest->points < $freshRoom->stake) {
                    return 'insufficient';
                }

                $guest->decrement('points', $freshRoom->stake);
            }

            $freshRoom->update([
                'guest_id' => $guestId,
                'status' => 'active',
                'turn' => 'red',
                'board' => GameState::initial()->board->toArray(),
                'move_history' => [],
                'started_at' => now(),
                'red_remaining_ms' => $freshRoom->time_control ? $freshRoom->time_control * 1000 : null,
                'black_remaining_ms' => $freshRoom->time_control ? $freshRoom->time_control * 1000 : null,
                'turn_started_at' => $freshRoom->time_control ? now() : null,
            ]);

            return 'ok';
        });

        if ($outcome === 'taken') {
            return response()->json(['message' => 'This room is no longer open to join.'], 422);
        }

        if ($outcome === 'insufficient') {
            return response()->json(['message' => 'You do not have enough points for this stake.'], 422);
        }

        $payload = $this->present($room->fresh('host', 'guest'));
        $this->broadcastRoomUpdate($room->id, $payload);

        return response()->json($payload);
    }

    public function move(Request $request, Room $room): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'array'],
            'from.x' => ['required', 'integer'],
            'from.y' => ['required', 'integer'],
            'to' => ['required', 'array'],
            'to.x' => ['required', 'integer'],
            'to.y' => ['required', 'integer'],
        ]);

        if ($room->status !== 'active') {
            return response()->json(['message' => 'This game is not in progress.'], 422);
        }

        $side = match ($request->user()->id) {
            $room->host_id => Side::Red,
            $room->guest_id => Side::Black,
            default => null,
        };

        if ($side === null) {
            return response()->json(['message' => 'You are not a player in this room.'], 403);
        }

        if ($room->turn !== $side->value) {
            return response()->json(['message' => 'It is not your turn.'], 422);
        }

        if ($this->hasTimedOut($room, $side)) {
            $this->finishGame($room, $side->opponent(), 'timeout');

            return response()->json($this->present($room->fresh('host', 'guest')));
        }

        $status = GameStatusResolver::resolve(Board::fromArray($room->board), Side::from($room->turn));
        $state = GameState::fromArray($room->board, $room->turn, $room->move_history, $status->value);

        try {
            $next = GameEngine::makeMove(
                $state,
                Position::fromArray($data['from']),
                Position::fromArray($data['to']),
            );
        } catch (IllegalMoveException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $room->board = $next->board->toArray();
        $room->turn = $next->turn->value;
        $room->move_history = array_map(fn ($m) => $m->toArray(), $next->moveHistory);

        if ($room->time_control) {
            $elapsedMs = (int) $room->turn_started_at->diffInMilliseconds(now());
            $field = $side === Side::Red ? 'red_remaining_ms' : 'black_remaining_ms';
            $room->{$field} = max(0, $room->{$field} - $elapsedMs);
            $room->turn_started_at = now();
        }

        if ($next->status === GameStatus::Checkmate || $next->status === GameStatus::Stalemate) {
            // Whoever just moved delivered mate (or stalemated the opponent,
            // which is also a loss in Xiangqi) - they win either way.
            $room->save();
            $this->finishGame($room, $state->turn, $next->status === GameStatus::Checkmate ? 'checkmate' : 'stalemate');

            return response()->json($this->present($room->fresh('host', 'guest')));
        }

        $room->save();

        $payload = $this->present($room->fresh('host', 'guest'));
        $this->broadcastRoomUpdate($room->id, $payload);

        return response()->json($payload);
    }

    /**
     * Either player (or the page polling in the background) can call this to
     * end the game once the side to move has run out of clock time, even if
     * they never submit another move themselves.
     */
    public function claimTimeout(Room $room): JsonResponse
    {
        if ($room->status !== 'active' || ! $room->time_control) {
            return response()->json(['message' => 'This game has no active clock.'], 422);
        }

        $side = Side::from($room->turn);

        if (! $this->hasTimedOut($room, $side)) {
            return response()->json(['message' => 'The side to move has not timed out yet.'], 422);
        }

        $this->finishGame($room, $side->opponent(), 'timeout');

        return response()->json($this->present($room->fresh('host', 'guest')));
    }

    private function hasTimedOut(Room $room, Side $sideToMove): bool
    {
        if (! $room->time_control || ! $room->turn_started_at) {
            return false;
        }

        $remaining = $sideToMove === Side::Red ? $room->red_remaining_ms : $room->black_remaining_ms;
        $elapsedMs = $room->turn_started_at->diffInMilliseconds(now());

        return $elapsedMs >= $remaining;
    }

    private function finishGame(Room $room, Side $winnerSide, string $reason): void
    {
        $room->status = 'finished';
        $room->ended_at = now();
        $room->winner_id = $winnerSide === Side::Red ? $room->host_id : $room->guest_id;
        $room->result = $winnerSide === Side::Red ? 'red_win' : 'black_win';

        if ($reason === 'timeout') {
            $field = $winnerSide === Side::Red ? 'black_remaining_ms' : 'red_remaining_ms';
            $room->{$field} = 0;
        }

        $room->save();

        $winner = $winnerSide === Side::Red ? $room->host : $room->guest;
        $loser = $winnerSide === Side::Red ? $room->guest : $room->host;

        $this->applyRatingChange(winner: $winner, loser: $loser);

        // Both sides' stakes were escrowed (deducted) up front at create/join
        // time, so the winner simply collects the full pot - the loser's
        // share never comes back.
        if ($room->stake > 0) {
            $winner->increment('points', $room->stake * 2);
        }

        $payload = $this->present($room->fresh('host', 'guest'));
        $this->broadcastRoomUpdate($room->id, $payload);
    }

    /**
     * Realtime push is a nice-to-have, not a hard requirement - a client can
     * always re-fetch the room. Never let a broadcasting failure (e.g. the
     * Reverb server being down) take down an otherwise-successful request.
     */
    private function broadcastRoomUpdate(int $roomId, array $payload): void
    {
        try {
            broadcast(new RoomUpdated($roomId, $payload));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function applyRatingChange(User $winner, User $loser): void
    {
        $expectedWinner = 1 / (1 + 10 ** (($loser->rating - $winner->rating) / 400));
        $delta = (int) round(32 * (1 - $expectedWinner));

        $winner->rating += $delta;
        $winner->wins += 1;
        $winner->save();

        $loser->rating = max(100, $loser->rating - $delta);
        $loser->losses += 1;
        $loser->save();
    }

    private function present(Room $room): array
    {
        $gameStatus = null;
        if ($room->board !== null) {
            $gameStatus = GameStatusResolver::resolve(Board::fromArray($room->board), Side::from($room->turn))->value;

            // A finished room whose final position isn't itself a mate/stalemate
            // ended some other way - currently that only ever means a clock
            // timeout (there is no resign/draw-offer flow yet).
            if ($room->status === 'finished' && ! in_array($gameStatus, ['checkmate', 'stalemate'], true)) {
                $gameStatus = 'timeout';
            }
        }

        return [
            'id' => $room->id,
            'code' => $room->code,
            'stake' => $room->stake,
            'status' => $room->status,
            'result' => $room->result,
            'turn' => $room->turn,
            'board' => $room->board,
            'moveHistory' => $room->move_history ?? [],
            'gameStatus' => $gameStatus,
            'host' => $room->host ? ['id' => $room->host->id, 'name' => $room->host->name] : null,
            'guest' => $room->guest ? ['id' => $room->guest->id, 'name' => $room->guest->name] : null,
            'winnerId' => $room->winner_id,
            'timeControl' => $room->time_control,
            'redRemainingMs' => $room->red_remaining_ms,
            'blackRemainingMs' => $room->black_remaining_ms,
            'turnStartedAt' => $room->turn_started_at?->toISOString(),
            'serverTime' => Carbon::now()->toISOString(),
        ];
    }
}
