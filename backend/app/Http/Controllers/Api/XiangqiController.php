<?php

namespace App\Http\Controllers\Api;

use App\Games\Xiangqi\Ai\MinimaxAi;
use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\GameEngine;
use App\Games\Xiangqi\GameState;
use App\Games\Xiangqi\IllegalMoveException;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Rules;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stateless move-validation endpoint backed by the Xiangqi rule engine.
 * The client (frontend) owns the game state between requests and resends it
 * with every move - there is no Room/DB persistence here yet, this only
 * exists to let a hot-seat game be played end-to-end against real rules.
 */
class XiangqiController extends Controller
{
    /** difficulty => [maxDepth, timeLimitSeconds, randomTopN]. */
    private const DIFFICULTIES = [
        'easy' => [1, 2.0, 4],
        'medium' => [2, 4.0, 2],
        'hard' => [3, 8.0, 1],
    ];

    public function newGame(): JsonResponse
    {
        return response()->json(GameState::initial()->toArray());
    }

    public function move(Request $request): JsonResponse
    {
        $data = $request->validate([
            'board' => ['required', 'array'],
            'turn' => ['required', 'string'],
            'moveHistory' => ['array'],
            'status' => ['required', 'string'],
            'from' => ['required', 'array'],
            'from.x' => ['required', 'integer'],
            'from.y' => ['required', 'integer'],
            'to' => ['required', 'array'],
            'to.x' => ['required', 'integer'],
            'to.y' => ['required', 'integer'],
        ]);

        try {
            $state = GameState::fromArray(
                $data['board'],
                $data['turn'],
                $data['moveHistory'] ?? [],
                $data['status'],
            );

            $next = GameEngine::makeMove(
                $state,
                Position::fromArray($data['from']),
                Position::fromArray($data['to']),
            );
        } catch (IllegalMoveException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid game state.'], 422);
        }

        return response()->json($next->toArray());
    }

    /**
     * Stateless: given the current state, the AI picks and plays a move for
     * whichever side is to move, returning the resulting state in the same
     * shape as move() so the frontend can treat it identically to a human move.
     */
    public function aiMove(Request $request): JsonResponse
    {
        $data = $request->validate([
            'board' => ['required', 'array'],
            'turn' => ['required', 'string'],
            'moveHistory' => ['array'],
            'status' => ['required', 'string'],
            'difficulty' => ['nullable', 'string', 'in:easy,medium,hard'],
        ]);

        [$maxDepth, $timeLimit, $randomTopN] = self::DIFFICULTIES[$data['difficulty'] ?? 'medium'];

        try {
            $state = GameState::fromArray(
                $data['board'],
                $data['turn'],
                $data['moveHistory'] ?? [],
                $data['status'],
            );

            $move = MinimaxAi::chooseMove($state, $maxDepth, $timeLimit, $randomTopN);
            $next = GameEngine::makeMove($state, $move->from, $move->to);
        } catch (IllegalMoveException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid game state.'], 422);
        }

        return response()->json($next->toArray());
    }

    public function legalMoves(Request $request): JsonResponse
    {
        $data = $request->validate([
            'board' => ['required', 'array'],
            'from' => ['required', 'array'],
            'from.x' => ['required', 'integer'],
            'from.y' => ['required', 'integer'],
        ]);

        try {
            $board = Board::fromArray($data['board']);
            $moves = Rules::getLegalMoves($board, Position::fromArray($data['from']));
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid game state.'], 422);
        }

        return response()->json([
            'moves' => array_map(fn ($m) => $m->to->toArray(), $moves),
        ]);
    }
}
