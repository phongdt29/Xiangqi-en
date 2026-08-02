<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper over PayPal's Orders v2 REST API (no official SDK dependency -
 * this app only ever needs "create order" + "capture order", both single
 * HTTP calls once authenticated).
 */
class PayPalClient
{
    private string $baseUrl;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        string $mode,
    ) {
        $this->baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Creates a PayPal order for the given amount and returns its order ID.
     */
    public function createOrder(string $amountUsd): string
    {
        $response = Http::withToken($this->accessToken())
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    ['amount' => ['currency_code' => 'USD', 'value' => $amountUsd]],
                ],
            ])
            ->throw();

        return $response->json('id');
    }

    /**
     * Captures a previously-created order. Returns the raw PayPal response so
     * the caller can check `status` (e.g. "COMPLETED") before crediting anything.
     */
    public function captureOrder(string $orderId): array
    {
        return Http::withToken($this->accessToken())
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture")
            ->throw()
            ->json();
    }

    /**
     * OAuth2 client-credentials token, cached for its own lifetime (PayPal
     * tokens are valid ~9h) so we don't re-authenticate on every request.
     */
    private function accessToken(): string
    {
        return Cache::remember('paypal:access_token:'.$this->mode(), 3600, function () {
            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post("{$this->baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
                ->throw();

            $token = $response->json('access_token');

            if (! $token) {
                throw new RuntimeException('PayPal did not return an access token.');
            }

            return $token;
        });
    }

    private function mode(): string
    {
        return str_contains($this->baseUrl, 'sandbox') ? 'sandbox' : 'live';
    }
}
