<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
