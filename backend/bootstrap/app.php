<?php

use App\Http\Middleware\BlockCountry;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // The frontend authenticates with a Sanctum bearer token, not a session
    // cookie, so /broadcasting/auth must run under auth:sanctum instead of
    // the default 'web' (session) middleware - registered explicitly here
    // rather than via withRouting's implicit `channels` param.
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-only app: never redirect unauthenticated requests to a "login"
        // route (none exists) - let them fall through to a JSON 401 instead.
        Authenticate::redirectUsing(fn () => null);

        $middleware->api(append: [BlockCountry::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
