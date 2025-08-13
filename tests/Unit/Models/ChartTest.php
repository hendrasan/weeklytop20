<?php

use App\Models\Chart;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Chart Model', function () {
    test('has user relationship', function () {
        $chart = new Chart();

        expect($chart->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    test('belongs to user', function () {
        $user = User::factory()->create();
        $chart = Chart::factory()->create(['user_id' => $user->id]);

        expect($chart->user->id)->toBe($user->id);
    });

    test('withChartRuns scope adds chart runs data', function () {
        $user = User::factory()->create();

        // Create multiple charts for the same track
        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track123',
            'position' => 5,
            'period' => 1,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track123',
            'position' => 3,
            'period' => 2,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track123',
            'position' => 1,
            'period' => 3,
        ]);

        $chart = Chart::withChartRuns($user->id)
            ->where('user_id', $user->id)
            ->where('track_spotify_id', 'track123')
            ->where('period', 3)
            ->first();

        expect($chart->chart_runs)->not()->toBeNull();

        // Parse the JSON array and verify positions
        $chartRuns = json_decode($chart->chart_runs, true);
        expect($chartRuns)->toContain(5, 3, 1);
    });

    test('can create chart with all required fields', function () {
        $user = User::factory()->create();

        $chartData = [
            'user_id' => $user->id,
            'period' => 1,
            'track_spotify_id' => 'spotify123',
            'track_name' => 'Test Song',
            'track_artist' => 'Test Artist',
            'track_data' => json_encode(['id' => 'spotify123', 'name' => 'Test Song']),
            'position' => 1,
            'last_position' => null,
            'periods_on_chart' => 1,
            'peak_position' => 1,
            'is_reentry' => null,
        ];

        $chart = Chart::create($chartData);

        expect($chart->user_id)->toBe($user->id);
        expect($chart->track_name)->toBe('Test Song');
        expect($chart->track_artist)->toBe('Test Artist');
        expect($chart->position)->toBe(1);
    });

    test('track_data can store complex json', function () {
        $user = User::factory()->create();

        $trackData = [
            'id' => 'spotify123',
            'name' => 'Test Song',
            'artists' => [
                ['name' => 'Artist 1'],
                ['name' => 'Artist 2']
            ],
            'album' => [
                'name' => 'Test Album',
                'images' => [
                    ['url' => 'https://example.com/image.jpg']
                ]
            ]
        ];

        $chart = Chart::factory()->create([
            'user_id' => $user->id,
            'track_data' => json_encode($trackData),
        ]);

        $decodedData = json_decode($chart->track_data, true);
        expect($decodedData['name'])->toBe('Test Song');
        expect($decodedData['artists'])->toHaveCount(2);
        expect($decodedData['album']['name'])->toBe('Test Album');
    });
});
