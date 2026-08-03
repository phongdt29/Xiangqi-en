<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomStakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_staked_room_escrows_the_hosts_points(): void
    {
        $host = User::factory()->create(['points' => 1000]);

        $response = $this->actingAs($host)->postJson('/api/rooms', ['stake' => 200]);

        $response->assertCreated();
        $response->assertJson(['stake' => 200]);
        $this->assertSame(800, $host->fresh()->points);
    }

    public function test_cannot_create_a_room_with_a_stake_below_the_minimum(): void
    {
        $host = User::factory()->create(['points' => 1000]);

        $response = $this->actingAs($host)->postJson('/api/rooms', ['stake' => 50]);

        $response->assertStatus(422);
        $this->assertSame(1000, $host->fresh()->points);
        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_a_zero_stake_room_is_still_allowed(): void
    {
        $host = User::factory()->create(['points' => 100]);

        $response = $this->actingAs($host)->postJson('/api/rooms', ['stake' => 0]);

        $response->assertCreated();
        $this->assertSame(100, $host->fresh()->points);
    }

    public function test_cannot_create_a_staked_room_without_enough_points(): void
    {
        $host = User::factory()->create(['points' => 10]);

        $response = $this->actingAs($host)->postJson('/api/rooms', ['stake' => 200]);

        $response->assertStatus(422);
        $this->assertSame(10, $host->fresh()->points);
        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_joining_a_staked_room_escrows_the_guests_points(): void
    {
        $host = User::factory()->create(['points' => 1000]);
        $guest = User::factory()->create(['points' => 1000]);
        $room = $this->createStakedRoom($host, 200);

        $response = $this->actingAs($guest)->postJson("/api/rooms/{$room->id}/join");

        $response->assertOk();
        $this->assertSame(800, $guest->fresh()->points);
    }

    public function test_cannot_join_a_staked_room_without_enough_points(): void
    {
        $host = User::factory()->create(['points' => 1000]);
        $guest = User::factory()->create(['points' => 10]);
        $room = $this->createStakedRoom($host, 200);

        $response = $this->actingAs($guest)->postJson("/api/rooms/{$room->id}/join");

        $response->assertStatus(422);
        $this->assertSame(10, $guest->fresh()->points);
        $this->assertSame('waiting', $room->fresh()->status);
    }

    public function test_winner_collects_the_full_pot_when_the_opponent_times_out(): void
    {
        $host = User::factory()->create(['points' => 1000]);
        $guest = User::factory()->create(['points' => 1000]);
        $room = $this->createStakedRoom($host, 200, timeControl: 300);

        $this->actingAs($guest)->postJson("/api/rooms/{$room->id}/join")->assertOk();

        // Host (red, to move first) has run out of clock time.
        $room->refresh();
        $room->update(['red_remaining_ms' => 1000, 'turn_started_at' => now()->subSeconds(5)]);

        $response = $this->actingAs($guest)->postJson("/api/rooms/{$room->id}/claim-timeout");

        $response->assertOk();
        $this->assertSame('black_win', $response->json('result'));
        $this->assertSame(1200, $guest->fresh()->points); // 1000 - 200 stake + 400 pot
        $this->assertSame(800, $host->fresh()->points); // 1000 - 200 stake, never refunded
    }

    public function test_host_can_cancel_a_waiting_staked_room_and_is_refunded(): void
    {
        $host = User::factory()->create(['points' => 1000]);
        $room = $this->createStakedRoom($host, 200);

        $response = $this->actingAs($host)->postJson("/api/rooms/{$room->id}/cancel");

        $response->assertOk();
        $this->assertSame('abandoned', $response->json('status'));
        $this->assertSame(1000, $host->fresh()->points);
    }

    public function test_only_the_host_can_cancel_a_room(): void
    {
        $host = User::factory()->create(['points' => 1000]);
        $stranger = User::factory()->create();
        $room = $this->createStakedRoom($host, 200);

        $response = $this->actingAs($stranger)->postJson("/api/rooms/{$room->id}/cancel");

        $response->assertStatus(403);
        $this->assertSame(800, $host->fresh()->points);
    }

    public function test_cannot_cancel_a_room_that_already_started(): void
    {
        $host = User::factory()->create(['points' => 1000]);
        $guest = User::factory()->create(['points' => 1000]);
        $room = $this->createStakedRoom($host, 200);
        $this->actingAs($guest)->postJson("/api/rooms/{$room->id}/join")->assertOk();

        $response = $this->actingAs($host)->postJson("/api/rooms/{$room->id}/cancel");

        $response->assertStatus(422);
    }

    private function createStakedRoom(User $host, int $stake, ?int $timeControl = null): Room
    {
        $response = $this->actingAs($host)->postJson('/api/rooms', [
            'stake' => $stake,
            'time_control' => $timeControl,
        ])->assertCreated();

        return Room::findOrFail($response->json('id'));
    }
}
