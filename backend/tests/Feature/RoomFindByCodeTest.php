<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomFindByCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_a_room_by_its_share_code(): void
    {
        $host = User::factory()->create();
        $room = $this->actingAs($host)->postJson('/api/rooms', ['stake' => 0])->json();

        $response = $this->actingAs(User::factory()->create())
            ->getJson('/api/rooms/find-by-code?code='.$room['code']);

        $response->assertOk();
        $response->assertJson(['id' => $room['id']]);
    }

    public function test_returns_404_for_an_unknown_code(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->getJson('/api/rooms/find-by-code?code=000000');

        $response->assertStatus(404);
    }
}
