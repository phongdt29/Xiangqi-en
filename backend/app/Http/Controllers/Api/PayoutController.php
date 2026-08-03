<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Services\PayPalClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    public function __construct(private readonly PayPalClient $paypal) {}

    public function index(Request $request): JsonResponse
    {
        $payouts = $request->user()->payoutRequests()->latest()->limit(50)->get();

        return response()->json(['payouts' => $payouts]);
    }

    /**
     * Cashes points out to a PayPal account. Requires the account's current
     * password (not just a valid bearer token) so a leaked/stolen token
     * alone can't drain a balance. Points are escrowed (deducted) before
     * calling PayPal so a slow/retried request can never double-spend the
     * same balance; a failed PayPal call refunds them.
     */
    public function store(Request $request): JsonResponse
    {
        $minimum = config('points.withdraw_minimum');
        $rate = config('points.withdraw_rate');

        $data = $request->validate([
            'points' => ['required', 'integer', "min:{$minimum}"],
            'paypal_email' => ['required', 'email'],
            'password' => ['required', 'current_password'],
        ]);

        $amountUsd = round($data['points'] / $rate, 2);
        $userId = $request->user()->id;

        // Row-locked so two concurrent withdrawal requests can never both
        // pass the balance check before either decrements - without this,
        // firing the same request twice in parallel can overdraw the balance.
        $payout = DB::transaction(function () use ($userId, $data, $amountUsd) {
            $user = User::whereKey($userId)->lockForUpdate()->first();

            if ($user->points < $data['points']) {
                return null;
            }

            $user->decrement('points', $data['points']);

            return PayoutRequest::create([
                'user_id' => $user->id,
                'points' => $data['points'],
                'amount_usd' => $amountUsd,
                'paypal_email' => $data['paypal_email'],
                'status' => 'pending',
            ]);
        });

        if (! $payout) {
            return response()->json(['message' => 'You do not have enough points for this withdrawal.'], 422);
        }

        try {
            $result = $this->paypal->sendPayout($data['paypal_email'], number_format($amountUsd, 2, '.', ''));

            $payout->update([
                'status' => 'completed',
                'paypal_batch_id' => $result['batch_header']['payout_batch_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $payout->update(['status' => 'failed']);
            User::whereKey($userId)->increment('points', $data['points']);

            report($e);

            return response()->json(['message' => 'PayPal could not process this payout. Your points have been refunded.'], 422);
        }

        return response()->json(['payout' => $payout->fresh(), 'balance' => User::find($userId)->points], 201);
    }
}
