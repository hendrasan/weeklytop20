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
        Schema::table('charts', function (Blueprint $table) {
            $table->index('user_id');
            $table->index(['user_id', 'track_artist']);
            $table->index(['user_id', 'period']);
            $table->index(['track_spotify_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charts', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['user_id', 'track_artist']);
            $table->dropIndex(['user_id', 'period']);
            $table->dropIndex(['track_spotify_id', 'period']);
        });
    }
};
