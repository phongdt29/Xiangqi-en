<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('co_up_games', function (Blueprint $table) {
            $table->id();
            // Always the unmasked ground truth - masking only ever happens
            // in the HTTP response, never in storage.
            $table->json('board');
            $table->enum('turn', ['red', 'black'])->default('red');
            $table->json('move_history');
            $table->enum('status', ['active', 'check', 'checkmate', 'stalemate'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('co_up_games');
    }
};
