<?php

namespace Database\Seeders;

use App\Games\Xiangqi\Board;
use App\Games\Xiangqi\Piece;
use App\Games\Xiangqi\PieceType;
use App\Games\Xiangqi\Position;
use App\Games\Xiangqi\Side;
use App\Models\Puzzle;
use Illuminate\Database\Seeder;

/**
 * Every position here was verified with a standalone script before being
 * committed: the side to move must not already be in check, and there must
 * be exactly one legal move that delivers checkmate (see the session notes -
 * several other hand-built candidates looked right but weren't, because an
 * "escape" square was still reachable by an interposing piece or a covering
 * piece was also directly checking the general from square one).
 */
class PuzzleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->puzzles() as $puzzle) {
            Puzzle::create($puzzle);
        }
    }

    /** @return array<int, array{title: string, board: array, turn: string, mate_in: int, difficulty: string}> */
    private function puzzles(): array
    {
        return [
            [
                'title' => 'Open File Mate',
                'board' => $this->board([
                    [4, 9, PieceType::General, Side::Black],
                    [1, 8, PieceType::Horse, Side::Red],
                    [7, 8, PieceType::Horse, Side::Red],
                    [7, 1, PieceType::Chariot, Side::Red],
                ]),
                'turn' => 'red',
                'mate_in' => 1,
                'difficulty' => 'easy',
            ],
            [
                'title' => 'Open File Mate (mirrored)',
                'board' => $this->board([
                    [4, 0, PieceType::General, Side::Red],
                    [1, 1, PieceType::Horse, Side::Black],
                    [7, 1, PieceType::Horse, Side::Black],
                    [1, 8, PieceType::Chariot, Side::Black],
                ]),
                'turn' => 'black',
                'mate_in' => 1,
                'difficulty' => 'easy',
            ],
            [
                'title' => 'Cannon Screen Mate',
                'board' => $this->board([
                    [4, 9, PieceType::General, Side::Black],
                    [1, 8, PieceType::Horse, Side::Red],
                    [7, 8, PieceType::Horse, Side::Red],
                    [4, 5, PieceType::Soldier, Side::Red],
                    [2, 3, PieceType::Cannon, Side::Red],
                ]),
                'turn' => 'red',
                'mate_in' => 1,
                'difficulty' => 'medium',
            ],
            [
                'title' => 'Cannon Screen Mate (mirrored)',
                'board' => $this->board([
                    [4, 0, PieceType::General, Side::Red],
                    [1, 1, PieceType::Horse, Side::Black],
                    [7, 1, PieceType::Horse, Side::Black],
                    [4, 4, PieceType::Soldier, Side::Black],
                    [2, 6, PieceType::Cannon, Side::Black],
                ]),
                'turn' => 'black',
                'mate_in' => 1,
                'difficulty' => 'medium',
            ],
        ];
    }

    /** @param  array<int, array{0: int, 1: int, 2: PieceType, 3: Side}>  $placements */
    private function board(array $placements): array
    {
        $board = new Board;
        foreach ($placements as [$x, $y, $type, $side]) {
            $board->set(new Position($x, $y), new Piece($type, $side));
        }

        return $board->toArray();
    }
}
