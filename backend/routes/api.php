<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\XiangqiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::post('/rooms/{room}/join', [RoomController::class, 'join']);
    Route::post('/rooms/{room}/move', [RoomController::class, 'move']);
});

Route::get('/leaderboard', [LeaderboardController::class, 'index']);

Route::post('/xiangqi/new', [XiangqiController::class, 'newGame']);
Route::post('/xiangqi/move', [XiangqiController::class, 'move']);
Route::post('/xiangqi/legal-moves', [XiangqiController::class, 'legalMoves']);
