<?php

namespace App\Http\Controllers\Api;

use App\Games\Xiangqi\CoUp\CoUpBoard;
use App\Games\Xiangqi\CoUp\CoUpGameEngine;
use App\Games\Xiangqi\CoUp\CoUpGameState;
use App\Games\Xiangqi\CoUp\CoUpRules;
use App\Games\Xiangqi\IllegalMoveException;
use App\Games\Xiangqi\Position;
use App\Http\Controllers\Controller;
use App\Models\CoUpGame;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cờ Úp (hidden-piece Xiangqi variant) is server-authoritative and persisted
 * - unlike the stateless /api/xiangqi/* endpoints, the client can never be
 * trusted with the full board (it would leak every unrevealed piece's true
 * identity), so the server holds ground truth and only ever hands back a
 * masked view. No auth: a game id is a casual, shareable "room code", the
 * same trust model as the stateless hot-seat mode.
 */
class CoUpGameController extends Controller
{
    public function store(): JsonResponse
    {
        $game = CoUpGame::create([
            'board' => CoUpBoard::initial()->toArray(mask: false),
            'turn' => 'red',
            'move_history' => [],
            'status' => 'active',
        ]);

        return response()->json($this->present($game), 201);
    }

    public function show(CoUpGame $game): JsonResponse
    {
        return response()->json($this->present($game));
    }

    public function legalMoves(Request $request, CoUpGame $game): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'array'],
            'from.x' => ['required', 'integer'],
            'from.y' => ['required', 'integer'],
        ]);

        $board = CoUpBoard::fromArray($game->board);
        $moves = CoUpRules::getLegalMoves($board, Position::fromArray($data['from']));

        return response()->json([
            'moves' => array_map(fn ($m) => $m->to->toArray(), $moves),
        ]);
    }

    public function move(Request $request, CoUpGame $game): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'array'],
            'from.x' => ['required', 'integer'],
            'from.y' => ['required', 'integer'],
            'to' => ['required', 'array'],
            'to.x' => ['required', 'integer'],
            'to.y' => ['required', 'integer'],
        ]);

        $state = CoUpGameState::fromArray($game->board, $game->turn, $game->move_history, $game->status);

        try {
            $next = CoUpGameEngine::makeMove(
                $state,
                Position::fromArray($data['from']),
                Position::fromArray($data['to']),
            );
        } catch (IllegalMoveException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $game->update([
            'board' => $next->board->toArray(mask: false),
            'turn' => $next->turn->value,
            'move_history' => array_map(fn ($m) => $m->toArray(), $next->moveHistory),
            'status' => $next->status->value,
        ]);

        return response()->json($this->present($game));
    }

    private function present(CoUpGame $game): array
    {
        return [
            'id' => $game->id,
            'board' => CoUpBoard::fromArray($game->board)->toArray(mask: true),
            'turn' => $game->turn,
            'moveHistory' => $game->move_history,
            'status' => $game->status,
        ];
    }
}
