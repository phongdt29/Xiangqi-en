<?php

namespace Tests\Feature;

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PointsTest extends TestCase
{
    use RefreshDatabase;

    private function fakePayPal(string $captureStatus = 'COMPLETED'): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 32400]),
            '*/v2/checkout/orders/*/capture' => Http::response(['id' => 'ORDER-1', 'status' => $captureStatus]),
            '*/v2/checkout/orders' => Http::response(['id' => 'ORDER-1'], 201),
        ]);
    }

    public function test_lists_the_configured_packages(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/points/packages');

        $response->assertOk();
        $response->assertJsonStructure(['packages' => ['basic', 'plus', 'pro']]);
    }

    public function test_creates_a_paypal_order_using_the_server_side_price(): void
    {
        $this->fakePayPal();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/points/orders', ['package' => 'plus']);

        $response->assertCreated();
        $response->assertJson(['orderId' => 'ORDER-1']);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'package_key' => 'plus',
            'points' => 550,
            'paypal_order_id' => 'ORDER-1',
            'status' => 'created',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v2/checkout/orders')
            && ($request['purchase_units'][0]['amount']['value'] ?? null) === '5.00');
    }

    public function test_rejects_an_unknown_package(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/points/orders', ['package' => 'mega']);

        $response->assertStatus(422);
    }

    public function test_capturing_a_completed_order_credits_points(): void
    {
        $this->fakePayPal();
        $user = User::factory()->create(['points' => 0]);
        $transaction = PointTransaction::create([
            'user_id' => $user->id,
            'package_key' => 'basic',
            'points' => 100,
            'amount_usd' => 1.00,
            'paypal_order_id' => 'ORDER-1',
            'status' => 'created',
        ]);

        $response = $this->actingAs($user)->postJson('/api/points/orders/ORDER-1/capture');

        $response->assertOk();
        $response->assertJson(['balance' => 100, 'pointsAdded' => 100]);
        $this->assertSame(100, $user->fresh()->points);
        $this->assertSame('completed', $transaction->fresh()->status);
    }

    public function test_capturing_the_same_order_twice_does_not_double_credit(): void
    {
        $this->fakePayPal();
        $user = User::factory()->create(['points' => 0]);
        PointTransaction::create([
            'user_id' => $user->id,
            'package_key' => 'basic',
            'points' => 100,
            'amount_usd' => 1.00,
            'paypal_order_id' => 'ORDER-1',
            'status' => 'created',
        ]);

        $this->actingAs($user)->postJson('/api/points/orders/ORDER-1/capture')->assertOk();
        $this->actingAs($user)->postJson('/api/points/orders/ORDER-1/capture')->assertOk();

        $this->assertSame(100, $user->fresh()->points);
    }

    public function test_an_incomplete_paypal_capture_does_not_credit_points(): void
    {
        $this->fakePayPal(captureStatus: 'PENDING');
        $user = User::factory()->create(['points' => 0]);
        $transaction = PointTransaction::create([
            'user_id' => $user->id,
            'package_key' => 'basic',
            'points' => 100,
            'amount_usd' => 1.00,
            'paypal_order_id' => 'ORDER-1',
            'status' => 'created',
        ]);

        $response = $this->actingAs($user)->postJson('/api/points/orders/ORDER-1/capture');

        $response->assertStatus(422);
        $this->assertSame(0, $user->fresh()->points);
        $this->assertSame('failed', $transaction->fresh()->status);
    }

    public function test_cannot_capture_another_users_order(): void
    {
        $this->fakePayPal();
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        PointTransaction::create([
            'user_id' => $owner->id,
            'package_key' => 'basic',
            'points' => 100,
            'amount_usd' => 1.00,
            'paypal_order_id' => 'ORDER-1',
            'status' => 'created',
        ]);

        $response = $this->actingAs($intruder)->postJson('/api/points/orders/ORDER-1/capture');

        $response->assertStatus(404);
    }
}
