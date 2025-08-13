<?php

use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('Database and Data Integrity', function () {
    test('user model has correct database structure', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'spotify_id' => 'spotify123',
            'spotify_access_token' => 'access_token',
            'spotify_refresh_token' => 'refresh_token',
        ]);

        // Verify user was created with all fields
        expect($user->name)->toBe('Test User');
        expect($user->email)->toBe('test@example.com');
        expect($user->spotify_id)->toBe('spotify123');
        expect($user->spotify_access_token)->toBe('access_token');
        expect($user->spotify_refresh_token)->toBe('refresh_token');
        expect($user->created_at)->not()->toBeNull();
        expect($user->updated_at)->not()->toBeNull();
    });

    test('chart model has correct database structure', function () {
        $user = User::factory()->create();

        $chart = Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'track_spotify_id' => 'spotify_track_123',
            'track_name' => 'Test Song',
            'track_artist' => 'Test Artist',
            'track_data' => json_encode(['id' => 'spotify_track_123']),
            'position' => 5,
            'last_position' => 7,
            'periods_on_chart' => 2,
            'peak_position' => 3,
            'is_reentry' => 1,
        ]);

        expect($chart->user_id)->toBe($user->id);
        expect($chart->period)->toBe(1);
        expect($chart->track_spotify_id)->toBe('spotify_track_123');
        expect($chart->track_name)->toBe('Test Song');
        expect($chart->track_artist)->toBe('Test Artist');
        expect($chart->position)->toBe(5);
        expect($chart->last_position)->toBe(7);
        expect($chart->periods_on_chart)->toBe(2);
        expect($chart->peak_position)->toBe(3);
        expect($chart->is_reentry)->toBe(1);
    });

    test('chart foreign key relationship works', function () {
        $user = User::factory()->create();
        $chart = Chart::factory()->create(['user_id' => $user->id]);

        // Test the relationship
        expect($chart->user->id)->toBe($user->id);
        expect($user->charts->contains($chart))->toBeTrue();
    });

    test('track_data field can store complex json', function () {
        $user = User::factory()->create();

        $complexTrackData = [
            'id' => 'spotify123',
            'name' => 'Complex Song',
            'artists' => [
                ['name' => 'Artist 1', 'id' => 'artist1'],
                ['name' => 'Artist 2', 'id' => 'artist2']
            ],
            'album' => [
                'name' => 'Album Name',
                'images' => [
                    ['url' => 'https://example.com/large.jpg', 'width' => 640, 'height' => 640],
                    ['url' => 'https://example.com/medium.jpg', 'width' => 300, 'height' => 300],
                ],
                'release_date' => '2023-01-01'
            ],
            'duration_ms' => 240000,
            'popularity' => 85,
            'explicit' => false,
        ];

        $chart = Chart::factory()->create([
            'user_id' => $user->id,
            'track_data' => json_encode($complexTrackData),
        ]);

        $decodedData = json_decode($chart->track_data, true);

        expect($decodedData['id'])->toBe('spotify123');
        expect($decodedData['artists'])->toHaveCount(2);
        expect($decodedData['album']['images'])->toHaveCount(2);
        expect($decodedData['duration_ms'])->toBe(240000);
    });

    test('chart position constraints make sense', function () {
        $user = User::factory()->create();

        // Test valid positions (1-20)
        $chart1 = Chart::factory()->create([
            'user_id' => $user->id,
            'position' => 1,
        ]);

        $chart20 = Chart::factory()->create([
            'user_id' => $user->id,
            'position' => 20,
        ]);

        expect($chart1->position)->toBe(1);
        expect($chart20->position)->toBe(20);
    });

    test('multiple users can have same track at same position', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Chart::factory()->create([
            'user_id' => $user1->id,
            'track_spotify_id' => 'same_track',
            'position' => 1,
            'period' => 1,
        ]);

        Chart::factory()->create([
            'user_id' => $user2->id,
            'track_spotify_id' => 'same_track',
            'position' => 1,
            'period' => 1,
        ]);

        $chartsCount = Chart::where('track_spotify_id', 'same_track')
            ->where('position', 1)
            ->count();

        expect($chartsCount)->toBe(2);
    });

    test('user can have multiple periods', function () {
        $user = User::factory()->create();

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 1,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 2,
            'position' => 1,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 3,
            'position' => 1,
        ]);

        $periods = Chart::where('user_id', $user->id)
            ->distinct('period')
            ->pluck('period')
            ->sort()
            ->values();

        expect($periods->toArray())->toEqual([1, 2, 3]);
    });

    test('is_reentry field works as expected', function () {
        $user = User::factory()->create();

        // Regular entry
        $regularChart = Chart::factory()->create([
            'user_id' => $user->id,
            'is_reentry' => null,
        ]);

        // Reentry
        $reentryChart = Chart::factory()->create([
            'user_id' => $user->id,
            'is_reentry' => 1,
        ]);

        expect($regularChart->is_reentry)->toBeNull();
        expect($reentryChart->is_reentry)->toBe(1);

        // Check that we can query by reentry status
        $reentries = Chart::where('user_id', $user->id)
            ->where('is_reentry', 1)
            ->count();

        expect($reentries)->toBe(1);
    });

    test('withChartRuns scope produces valid json', function () {
        $user = User::factory()->create();

        // Create multiple chart entries for the same track
        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track1',
            'position' => 10,
            'period' => 1,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track1',
            'position' => 5,
            'period' => 2,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track1',
            'position' => 1,
            'period' => 3,
        ]);

        $chartWithRuns = Chart::withChartRuns($user->id)
            ->where('user_id', $user->id)
            ->where('track_spotify_id', 'track1')
            ->where('period', 3)
            ->first();

        expect($chartWithRuns->chart_runs)->not()->toBeNull();

        // Verify it's valid JSON
        $chartRuns = json_decode($chartWithRuns->chart_runs, true);
        expect(json_last_error())->toBe(JSON_ERROR_NONE);
        expect($chartRuns)->toBeArray();
        expect($chartRuns)->toContain(10, 5, 1);
    });
});
