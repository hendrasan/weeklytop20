<?php

use App\Models\User;
use App\Models\Chart;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('User Model', function () {
    test('has fillable attributes', function () {
        $fillable = [
            'name',
            'email',
            'password',
            'spotify_id',
            'spotify_access_token',
            'spotify_refresh_token'
        ];

        expect((new User)->getFillable())->toEqual($fillable);
    });

    test('has hidden attributes', function () {
        $hidden = [
            'password',
            'remember_token',
        ];

        expect((new User)->getHidden())->toEqual($hidden);
    });

    test('has charts relationship', function () {
        $user = User::factory()->create();

        expect($user->charts())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    test('latest_chart relationship returns correct chart', function () {
        $user = User::factory()->create();

        // Create charts for different periods
        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 1,
        ]);

        $latestChart = Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 2,
            'position' => 1,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 2,
            'position' => 2,
        ]);

        $result = $user->latest_chart;

        expect($result->id)->toBe($latestChart->id);
        expect($result->period)->toBe(2);
        expect($result->position)->toBe(1);
    });

    test('latest_chart_top_tracks returns top 3 tracks from latest period', function () {
        $user = User::factory()->create();

        // Create charts for latest period
        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 1,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 2,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 3,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 4, // This should not be included
        ]);

        $topTracks = $user->latest_chart_top_tracks;

        expect($topTracks)->toHaveCount(3);
        expect($topTracks->pluck('position')->toArray())->toEqual([1, 2, 3]);
    });

    test('casts email_verified_at to datetime', function () {
        $user = new User();

        expect($user->getCasts())->toHaveKey('email_verified_at', 'datetime');
    });

    test('casts password to hashed', function () {
        $user = new User();

        expect($user->getCasts())->toHaveKey('password', 'hashed');
    });
});
