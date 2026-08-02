<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointTransaction;
use App\Services\PayPalClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointsController extends Controller
{
    public function __construct(private readonly PayPalClient $paypal) {}

    public function packages(): JsonResponse
    {
        return response()->json([
            'packages' => config('points.packages'),
            'withdrawRate' => config('points.withdraw_rate'),
            'withdrawMinimum' => config('points.withdraw_minimum'),
        ]);
    }

    /**
     * Starts a top-up: creates a PayPal order for the package's server-side
     * price (the client only ever picks a package key, never an amount) and
     * records it as "created" so a later capture can be matched back to it.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'package' => ['required', 'string', 'in:'.implode(',', array_keys(config('points.packages')))],
        ]);

        $package = config("points.packages.{$data['package']}");

        $orderId = $this->paypal->createOrder(number_format($package['usd'], 2, '.', ''));

        PointTransaction::create([
            'user_id' => $request->user()->id,
            'package_key' => $data['package'],
            'points' => $package['points'],
            'amount_usd' => $package['usd'],
            'paypal_order_id' => $orderId,
            'status' => 'created',
        ]);

        return response()->json(['orderId' => $orderId], 201);
    }

    /**
     * Confirms payment with PayPal and credits points. Idempotent: capturing
     * the same order twice (e.g. a retried request) never double-credits.
     */
    public function capture(Request $request, string $orderId): JsonResponse
    {
        $transaction = PointTransaction::where('paypal_order_id', $orderId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($transaction->status === 'completed') {
            return response()->json(['balance' => $request->user()->points]);
        }

        $result = $this->paypal->captureOrder($orderId);

        if (($result['status'] ?? null) !== 'COMPLETED') {
            $transaction->update(['status' => 'failed']);

            return response()->json(['message' => 'Payment was not completed.'], 422);
        }

        $transaction->update(['status' => 'completed']);

        $user = $request->user();
        $user->increment('points', $transaction->points);

        return response()->json(['balance' => $user->fresh()->points, 'pointsAdded' => $transaction->points]);
    }
}
