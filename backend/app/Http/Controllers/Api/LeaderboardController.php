<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class LeaderboardController extends Controller
{
    public function index(): JsonResponse
    {
        $players = User::query()
            ->orderByDesc('rating')
            ->orderByDesc('wins')
            ->limit(50)
            ->get(['id', 'name', 'rating', 'wins', 'losses', 'draws']);

        return response()->json(['players' => $players]);
    }
}
