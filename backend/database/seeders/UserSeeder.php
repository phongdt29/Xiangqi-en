<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Dragon Master',
                'email' => 'dragon@example.com',
                'password' => Hash::make('password'),
                'rating' => 2850,
                'wins' => 342,
                'losses' => 45,
                'draws' => 18,
                'points' => 5420,
            ],
            [
                'name' => 'Phoenix Champion',
                'email' => 'phoenix@example.com',
                'password' => Hash::make('password'),
                'rating' => 2720,
                'wins' => 298,
                'losses' => 62,
                'draws' => 25,
                'points' => 4860,
            ],
            [
                'name' => 'Tiger Warrior',
                'email' => 'tiger@example.com',
                'password' => Hash::make('password'),
                'rating' => 2650,
                'wins' => 276,
                'losses' => 78,
                'draws' => 30,
                'points' => 4320,
            ],
            [
                'name' => 'Crane Master',
                'email' => 'crane@example.com',
                'password' => Hash::make('password'),
                'rating' => 2580,
                'wins' => 245,
                'losses' => 95,
                'draws' => 22,
                'points' => 3950,
            ],
            [
                'name' => 'Serpent Sage',
                'email' => 'serpent@example.com',
                'password' => Hash::make('password'),
                'rating' => 2520,
                'wins' => 228,
                'losses' => 110,
                'draws' => 18,
                'points' => 3580,
            ],
            [
                'name' => 'Lotus Zen',
                'email' => 'lotus@example.com',
                'password' => Hash::make('password'),
                'rating' => 2450,
                'wins' => 198,
                'losses' => 135,
                'draws' => 24,
                'points' => 3150,
            ],
            [
                'name' => 'Mountain Peak',
                'email' => 'mountain@example.com',
                'password' => Hash::make('password'),
                'rating' => 2380,
                'wins' => 165,
                'losses' => 160,
                'draws' => 28,
                'points' => 2680,
            ],
            [
                'name' => 'River Flow',
                'email' => 'river@example.com',
                'password' => Hash::make('password'),
                'rating' => 2310,
                'wins' => 142,
                'losses' => 185,
                'draws' => 20,
                'points' => 2245,
            ],
            [
                'name' => 'Forest Guard',
                'email' => 'forest@example.com',
                'password' => Hash::make('password'),
                'rating' => 2240,
                'wins' => 118,
                'losses' => 210,
                'draws' => 26,
                'points' => 1820,
            ],
            [
                'name' => 'Storm Rider',
                'email' => 'storm@example.com',
                'password' => Hash::make('password'),
                'rating' => 2150,
                'wins' => 95,
                'losses' => 245,
                'draws' => 18,
                'points' => 1420,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
