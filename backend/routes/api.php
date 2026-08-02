<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CoUpGameController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\PointsController;
use App\Http\Controllers\Api\PuzzleController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\XiangqiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/mine', [RoomController::class, 'mine']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::get('/rooms/{room}/replay', [RoomController::class, 'replay']);
    Route::post('/rooms/{room}/join', [RoomController::class, 'join']);
    Route::post('/rooms/{room}/move', [RoomController::class, 'move']);
    Route::post('/rooms/{room}/claim-timeout', [RoomController::class, 'claimTimeout']);

    Route::get('/points/packages', [PointsController::class, 'packages']);
    Route::post('/points/orders', [PointsController::class, 'createOrder']);
    Route::post('/points/orders/{orderId}/capture', [PointsController::class, 'capture']);
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
