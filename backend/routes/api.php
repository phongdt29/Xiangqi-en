<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CoUpGameController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\PointsController;
use App\Http\Controllers\Api\PuzzleController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\XiangqiController;
use Illuminate\Support\Facades\Route;

// Throttled by IP (unauthenticated) so credential-stuffing/brute-force
// against login, or mass account creation, can't be hammered freely.
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/mine', [RoomController::class, 'mine']);
    // Must come before the /rooms/{room} wildcard below, or "find-by-code"
    // itself gets swallowed as a {room} ID and 404s on the model binding.
    Route::get('/rooms/find-by-code', [RoomController::class, 'findByCode']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::post('/rooms/{room}/cancel', [RoomController::class, 'cancel']);
    Route::get('/rooms/{room}/replay', [RoomController::class, 'replay']);
    Route::post('/rooms/{room}/join', [RoomController::class, 'join']);
    Route::post('/rooms/{room}/move', [RoomController::class, 'move']);
    Route::post('/rooms/{room}/claim-timeout', [RoomController::class, 'claimTimeout']);

    Route::get('/points/packages', [PointsController::class, 'packages']);
    Route::post('/points/orders', [PointsController::class, 'createOrder'])->middleware('throttle:10,1');
    Route::post('/points/orders/{orderId}/capture', [PointsController::class, 'capture'])->middleware('throttle:10,1');

    Route::get('/payouts', [PayoutController::class, 'index']);
    // Deliberately tight: a real user withdraws a handful of times a day at
    // most - a burst of attempts here is either a bug or someone probing.
    Route::post('/payouts', [PayoutController::class, 'store'])->middleware('throttle:5,60');
});

Route::get('/leaderboard', [LeaderboardController::class, 'index']);

Route::get('/puzzles', [PuzzleController::class, 'index']);
Route::get('/puzzles/{puzzle}', [PuzzleController::class, 'show']);

Route::post('/xiangqi/new', [XiangqiController::class, 'newGame']);
Route::post('/xiangqi/move', [XiangqiController::class, 'move']);
Route::post('/xiangqi/legal-moves', [XiangqiController::class, 'legalMoves']);
Route::post('/xiangqi/ai-move', [XiangqiController::class, 'aiMove']);

Route::post('/co-up-games', [CoUpGameController::class, 'store']);
Route::get('/co-up-games/{game}', [CoUpGameController::class, 'show']);
Route::post('/co-up-games/{game}/legal-moves', [CoUpGameController::class, 'legalMoves']);
Route::post('/co-up-games/{game}/move', [CoUpGameController::class, 'move']);
