<?php

namespace App\Providers;

use App\Services\PayPalClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PayPalClient::class, fn () => new PayPalClient(
            clientId: config('services.paypal.client_id'),
            clientSecret: config('services.paypal.client_secret'),
            mode: config('services.paypal.mode'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Applies everywhere `Password::defaults()` is used (currently just
        // registration) - beyond the framework's bare min:8, since accounts
        // here hold real point balances now.
        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
