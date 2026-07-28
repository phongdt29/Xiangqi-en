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
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedInteger('time_control')->nullable()->after('move_history');
            $table->unsignedInteger('red_remaining_ms')->nullable()->after('time_control');
            $table->unsignedInteger('black_remaining_ms')->nullable()->after('red_remaining_ms');
            $table->timestamp('turn_started_at')->nullable()->after('black_remaining_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['time_control', 'red_remaining_ms', 'black_remaining_ms', 'turn_started_at']);
        });
    }
};
