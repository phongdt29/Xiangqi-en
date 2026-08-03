<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PayPalClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointsController extends Controller
{
    public function __construct(private readonly PayPalClient $paypal) {}

    public function packages(): JsonResponse
    {
        return response()->json([
            'packages' => config('points.packages'),
            'withdrawRate' => config('points.withdraw_rate'),
            'withdrawMinimum' => config('points.withdraw_minimum'),
            'minStake' => config('points.min_stake'),
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
     * the same order twice (e.g. two near-simultaneous retried requests)
     * never double-credits - the row lock is held for the whole operation
     * (including the PayPal call) so a second request for the same order
     * blocks until the first has committed its "completed" status, then
     * sees that status and short-circuits instead of crediting again.
     */
    public function capture(Request $request, string $orderId): JsonResponse
    {
        $userId = $request->user()->id;

        return DB::transaction(function () use ($orderId, $userId) {
            $transaction = PointTransaction::where('paypal_order_id', $orderId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->status === 'completed') {
                return response()->json(['balance' => User::find($userId)->points]);
            }

            $result = $this->paypal->captureOrder($orderId);

            if (($result['status'] ?? null) !== 'COMPLETED') {
                $transaction->update(['status' => 'failed']);

                return response()->json(['message' => 'Payment was not completed.'], 422);
            }

            $transaction->update(['status' => 'completed']);

            $user = User::whereKey($userId)->lockForUpdate()->first();
            $user->increment('points', $transaction->points);

            return response()->json(['balance' => $user->fresh()->points, 'pointsAdded' => $transaction->points]);
        });
    }
}
