<?php

namespace Tests\Feature;

use App\Games\Xiangqi\GameState;
use Tests\TestCase;

class XiangqiAiMoveTest extends TestCase
{
    public function test_ai_plays_a_legal_move_from_the_initial_position(): void
    {
        $initial = GameState::initial()->toArray();

        $response = $this->postJson('/api/xiangqi/ai-move', [...$initial, 'difficulty' => 'easy']);

        $response->assertOk();
        $response->assertJsonStructure(['board', 'turn', 'moveHistory', 'status']);
        $this->assertSame('black', $response->json('turn'));
        $this->assertCount(1, $response->json('moveHistory'));
    }

    public function test_rejects_an_unknown_difficulty(): void
    {
        $initial = GameState::initial()->toArray();

        $response = $this->postJson('/api/xiangqi/ai-move', [...$initial, 'difficulty' => 'impossible']);

        $response->assertStatus(422);
    }

    public function test_rejects_a_malformed_board_without_crashing(): void
    {
        $response = $this->postJson('/api/xiangqi/ai-move', [
            'board' => 'not-a-board',
            'turn' => 'red',
            'status' => 'active',
        ]);

        $response->assertStatus(422);
    }

    public function test_defaults_to_medium_difficulty_when_none_given(): void
    {
        $initial = GameState::initial()->toArray();

        $response = $this->postJson('/api/xiangqi/ai-move', $initial);

        $response->assertOk();
    }
}
