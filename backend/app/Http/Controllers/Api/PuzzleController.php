<?php

namespace App\Http\Controllers\Api;

use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\GameStatusResolver;
use App\Games\Xiangqi\Side;
use App\Http\Controllers\Controller;
use App\Models\Puzzle;
use Illuminate\Http\JsonResponse;

class PuzzleController extends Controller
{
    public function index(): JsonResponse
    {
        $puzzles = Puzzle::query()
            ->orderBy('id')
            ->get(['id', 'title', 'difficulty', 'mate_in'])
            ->map(fn (Puzzle $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'difficulty' => $p->difficulty,
                'mateIn' => $p->mate_in,
            ]);

        return response()->json(['puzzles' => $puzzles]);
    }

    public function show(Puzzle $puzzle): JsonResponse
    {
        $status = GameStatusResolver::resolve(Board::fromArray($puzzle->board), Side::from($puzzle->turn));

        return response()->json([
            'id' => $puzzle->id,
            'title' => $puzzle->title,
            'difficulty' => $puzzle->difficulty,
            'mateIn' => $puzzle->mate_in,
            'initial' => [
                'board' => $puzzle->board,
                'turn' => $puzzle->turn,
                'moveHistory' => [],
                'status' => $status->value,
            ],
        ]);
    }
}
