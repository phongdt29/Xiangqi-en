<?php

namespace Tests\Feature;

use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayoutTest extends TestCase
{
    use RefreshDatabase;

    private function fakePayPalPayout(int $status = 201): void
    {
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 32400]),
            '*/v1/payments/payouts' => Http::response(['batch_header' => ['payout_batch_id' => 'BATCH-1']], $status),
        ]);
    }

    public function test_withdrawing_points_escrows_them_and_records_a_completed_payout(): void
    {
        $this->fakePayPalPayout();
        $user = User::factory()->create(['points' => 1000]);

        $response = $this->actingAs($user)->postJson('/api/payouts', [
            'points' => 50,
            'paypal_email' => 'buyer@example.com',
            'password' => 'password',
        ]);

        $response->assertCreated();
        $response->assertJson(['balance' => 950]);
        $this->assertSame(950, $user->fresh()->points);

        $this->assertDatabaseHas('payout_requests', [
            'user_id' => $user->id,
            'points' => 50,
            'amount_usd' => '5.00',
            'paypal_batch_id' => 'BATCH-1',
            'status' => 'completed',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/payments/payouts')
            && $request['items'][0]['receiver'] === 'buyer@example.com'
            && $request['items'][0]['amount']['value'] === '5.00');
    }

    public function test_rejects_the_wrong_account_password(): void
    {
        $user = User::factory()->create(['points' => 1000]);

        $response = $this->actingAs($user)->postJson('/api/payouts', [
            'points' => 50,
            'paypal_email' => 'buyer@example.com',
            'password' => 'not-the-real-password',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1000, $user->fresh()->points);
        $this->assertDatabaseCount('payout_requests', 0);
    }

    public function test_cannot_withdraw_more_points_than_the_balance(): void
    {
        $user = User::factory()->create(['points' => 100]);

        $response = $this->actingAs($user)->postJson('/api/payouts', [
            'points' => 500,
            'paypal_email' => 'buyer@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $this->assertSame(100, $user->fresh()->points);
        $this->assertDatabaseCount('payout_requests', 0);
    }

    public function test_rejects_a_withdrawal_below_the_minimum(): void
    {
        $user = User::factory()->create(['points' => 1000]);

        $response = $this->actingAs($user)->postJson('/api/payouts', [
            'points' => 10,
            'paypal_email' => 'buyer@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1000, $user->fresh()->points);
    }

    public function test_a_failed_paypal_payout_refunds_the_escrowed_points(): void
    {
        $this->fakePayPalPayout(status: 400);
        $user = User::factory()->create(['points' => 1000]);

        $response = $this->actingAs($user)->postJson('/api/payouts', [
            'points' => 500,
            'paypal_email' => 'buyer@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1000, $user->fresh()->points);
        $this->assertDatabaseHas('payout_requests', ['user_id' => $user->id, 'status' => 'failed']);
    }

    public function test_lists_the_users_own_payout_history(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        PayoutRequest::create([
            'user_id' => $user->id,
            'points' => 500,
            'amount_usd' => 5.00,
            'paypal_email' => 'buyer@example.com',
            'status' => 'completed',
        ]);
        PayoutRequest::create([
            'user_id' => $other->id,
            'points' => 700,
            'amount_usd' => 7.00,
            'paypal_email' => 'someone-else@example.com',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->getJson('/api/payouts');

        $response->assertOk();
        $this->assertCount(1, $response->json('payouts'));
    }
}
