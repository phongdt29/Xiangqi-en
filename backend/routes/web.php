<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// This domain is the API only - a small styled status page instead of
// Laravel's default welcome page, which needlessly advertises the exact
// framework/PHP version to any browser visitor here.
Route::get('/', fn () => view('api-status'));

// One-time migration runner for hosts with no SSH access (e.g. shared cPanel).
// Only active when DEPLOY_MIGRATE_TOKEN is set in .env - remove that line
// once migrations have run to close this off again.
Route::get('/deploy/migrate', function () {
    $token = env('DEPLOY_MIGRATE_TOKEN');
    abort_unless($token && hash_equals($token, (string) request('token')), 404);

    Artisan::call('migrate', ['--force' => true]);
    $migrateOutput = Artisan::output();

    Artisan::call('db:seed', ['--force' => true]);
    $seedOutput = Artisan::output();

    return response("MIGRATE:\n{$migrateOutput}\nSEED:\n{$seedOutput}")->header('Content-Type', 'text/plain');
});
