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
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        $rooms = Room::query()
            ->where('status', 'waiting')
            ->with('host:id,name')
            ->latest()
            ->limit(50)
            ->get(['id', 'code', 'host_id', 'created_at']);

        return response()->json(['rooms' => $rooms]);
    }

    public function store(Request $request): JsonResponse
    {
        $room = Room::create([
            'code' => strtoupper(Str::random(6)),
            'host_id' => $request->user()->id,
            'status' => 'waiting',
            'turn' => 'red',
            'move_history' => [],
        ]);

        return response()->json($this->present($room->fresh('host', 'guest')), 201);
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json($this->present($room->load('host', 'guest')));
    }

    public function join(Request $request, Room $room): JsonResponse
    {
        if ($room->status !== 'waiting') {
            return response()->json(['message' => 'This room is no longer open to join.'], 422);
        }

        if ($room->host_id === $request->user()->id) {
            return response()->json(['message' => 'You cannot join your own room.'], 422);
        }

        $room->update([
            'guest_id' => $request->user()->id,
            'status' => 'active',
            'turn' => 'red',
            'board' => GameState::initial()->board->toArray(),
            'move_history' => [],
            'started_at' => now(),
        ]);

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
            $room->host_id => 'red',
            $room->guest_id => 'black',
            default => null,
        };

        if ($side === null) {
            return response()->json(['message' => 'You are not a player in this room.'], 403);
        }

        if ($room->turn !== $side) {
            return response()->json(['message' => 'It is not your turn.'], 422);
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

        if ($next->status === GameStatus::Checkmate || $next->status === GameStatus::Stalemate) {
            // Whoever just moved delivered mate (or stalemated the opponent,
            // which is also a loss in Xiangqi) - they win either way.
            $winnerSide = $state->turn;
            $room->status = 'finished';
            $room->ended_at = now();
            $room->winner_id = $winnerSide === Side::Red ? $room->host_id : $room->guest_id;
            $room->result = $winnerSide === Side::Red ? 'red_win' : 'black_win';

            $this->applyRatingChange(
                winner: $winnerSide === Side::Red ? $room->host : $room->guest,
                loser: $winnerSide === Side::Red ? $room->guest : $room->host,
            );
        }

        $room->save();

        $payload = $this->present($room->fresh('host', 'guest'));
        $this->broadcastRoomUpdate($room->id, $payload);

        return response()->json($payload);
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
        }

        return [
            'id' => $room->id,
            'code' => $room->code,
            'status' => $room->status,
            'result' => $room->result,
            'turn' => $room->turn,
            'board' => $room->board,
            'moveHistory' => $room->move_history ?? [],
            'gameStatus' => $gameStatus,
            'host' => $room->host ? ['id' => $room->host->id, 'name' => $room->host->name] : null,
            'guest' => $room->guest ? ['id' => $room->guest->id, 'name' => $room->guest->name] : null,
            'winnerId' => $room->winner_id,
        ];
    }
}
