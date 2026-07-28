<?php

use App\Http\Controllers\Api\XiangqiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/xiangqi/new', [XiangqiController::class, 'newGame']);
Route::post('/xiangqi/move', [XiangqiController::class, 'move']);
Route::post('/xiangqi/legal-moves', [XiangqiController::class, 'legalMoves']);
