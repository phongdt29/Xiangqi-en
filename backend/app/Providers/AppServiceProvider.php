<?php

namespace App\Providers;

use App\Services\PayPalClient;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
