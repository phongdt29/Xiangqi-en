<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Services\PayPalClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(private readonly PayPalClient $paypal) {}

    public function index(Request $request): JsonResponse
    {
        $payouts = $request->user()->payoutRequests()->latest()->limit(50)->get();

        return response()->json(['payouts' => $payouts]);
    }

    /**
     * Cashes points out to a PayPal account. Points are escrowed (deducted)
     * before calling PayPal so a slow/retried request can never double-spend
     * the same balance; a failed PayPal call refunds them.
     */
    public function store(Request $request): JsonResponse
    {
        $minimum = config('points.withdraw_minimum');
        $rate = config('points.withdraw_rate');

        $data = $request->validate([
            'points' => ['required', 'integer', "min:{$minimum}"],
            'paypal_email' => ['required', 'email'],
        ]);

        $user = $request->user();

        if ($user->points < $data['points']) {
            return response()->json(['message' => 'You do not have enough points for this withdrawal.'], 422);
        }

        $amountUsd = round($data['points'] / $rate, 2);

        $user->decrement('points', $data['points']);

        $payout = PayoutRequest::create([
            'user_id' => $user->id,
            'points' => $data['points'],
            'amount_usd' => $amountUsd,
            'paypal_email' => $data['paypal_email'],
            'status' => 'pending',
        ]);

        try {
            $result = $this->paypal->sendPayout($data['paypal_email'], number_format($amountUsd, 2, '.', ''));

            $payout->update([
                'status' => 'completed',
                'paypal_batch_id' => $result['batch_header']['payout_batch_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $payout->update(['status' => 'failed']);
            $user->increment('points', $data['points']);

            report($e);

            return response()->json(['message' => 'PayPal could not process this payout. Your points have been refunded.'], 422);
        }

        return response()->json(['payout' => $payout->fresh(), 'balance' => $user->fresh()->points], 201);
    }
}
