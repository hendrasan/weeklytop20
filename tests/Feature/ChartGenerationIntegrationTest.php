<?php

use App\Models\Chart;
use App\Models\User;
use App\Services\Spotify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

describe('Chart Generation Integration', function () {
    afterEach(function () {
        Mockery::close();
    });

    test('complete chart generation flow for new user', function () {
        // Create a new user
        $user = User::factory()->create([
            'spotify_id' => 'new_user_123',
            'spotify_access_token' => 'valid_token',
        ]);

        // Mock Spotify API response
        $mockTracks = (object) [
            'items' => [
                (object) [
                    'id' => 'track1',
                    'name' => 'Hit Song 1',
                    'artists' => [(object) ['name' => 'Artist 1']]
                ],
                (object) [
                    'id' => 'track2',
                    'name' => 'Hit Song 2',
                    'artists' => [(object) ['name' => 'Artist 2']]
                ],
                (object) [
                    'id' => 'track3',
                    'name' => 'Hit Song 3',
                    'artists' => [(object) ['name' => 'Artist 3']]
                ]
            ]
        ];

        // Mock the Spotify Web API
        $spotifyApiMock = Mockery::mock(\SpotifyWebAPI\SpotifyWebAPI::class);
        $spotifyApiMock->shouldReceive('setAccessToken')->once();
        $spotifyApiMock->shouldReceive('getMyTop')->once()->andReturn($mockTracks);

        $spotifyService = new Spotify($spotifyApiMock);

        // Generate chart
        $result = $spotifyService->generateChart($user);

        expect($result)->toBeTrue();

        // Verify charts were created
        $charts = Chart::where('user_id', $user->id)->orderBy('position')->get();

        expect($charts)->toHaveCount(3);
        expect($charts->pluck('track_name')->toArray())->toEqual([
            'Hit Song 1',
            'Hit Song 2',
            'Hit Song 3'
        ]);
        expect($charts->pluck('position')->toArray())->toEqual([1, 2, 3]);
        expect($charts->every(fn($chart) => $chart->period === 1))->toBeTrue();
        expect($charts->every(fn($chart) => $chart->periods_on_chart === 1))->toBeTrue();
    });

    test('chart progression over multiple periods', function () {
        $user = User::factory()->create();

        // Create initial chart (Period 1)
        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'track_spotify_id' => 'track1',
            'track_name' => 'Song A',
            'position' => 1,
            'created_at' => now()->subDays(5),
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'track_spotify_id' => 'track2',
            'track_name' => 'Song B',
            'position' => 2,
            'created_at' => now()->subDays(5),
        ]);

        // Mock new chart data for Period 2
        $mockTracks = (object) [
            'items' => [
                (object) [
                    'id' => 'track2', // Song B moves to #1
                    'name' => 'Song B',
                    'artists' => [(object) ['name' => 'Artist B']]
                ],
                (object) [
                    'id' => 'track3', // New song at #2
                    'name' => 'Song C',
                    'artists' => [(object) ['name' => 'Artist C']]
                ],
                (object) [
                    'id' => 'track1', // Song A drops to #3
                    'name' => 'Song A',
                    'artists' => [(object) ['name' => 'Artist A']]
                ]
            ]
        ];

        $spotifyApiMock = Mockery::mock(\SpotifyWebAPI\SpotifyWebAPI::class);
        $spotifyApiMock->shouldReceive('setAccessToken')->once();
        $spotifyApiMock->shouldReceive('getMyTop')->once()->andReturn($mockTracks);

        $spotifyService = new Spotify($spotifyApiMock);
        $result = $spotifyService->generateChart($user);

        expect($result)->toBeTrue();

        // Verify Period 2 charts
        $period2Charts = Chart::where('user_id', $user->id)
            ->where('period', 2)
            ->orderBy('position')
            ->get();

        expect($period2Charts)->toHaveCount(3);

        // Check Song B (moved from #2 to #1)
        $songB = $period2Charts->where('track_spotify_id', 'track2')->first();
        expect($songB->position)->toBe(1);
        expect($songB->last_position)->toBe(2);
        expect($songB->periods_on_chart)->toBe(2);
        expect($songB->peak_position)->toBe(1); // New peak

        // Check Song C (new entry)
        $songC = $period2Charts->where('track_spotify_id', 'track3')->first();
        expect($songC->position)->toBe(2);
        expect($songC->last_position)->toBeNull();
        expect($songC->periods_on_chart)->toBe(1);
        expect($songC->is_reentry)->toBeNull();

        // Check Song A (dropped from #1 to #3)
        $songA = $period2Charts->where('track_spotify_id', 'track1')->first();
        expect($songA->position)->toBe(3);
        expect($songA->last_position)->toBe(1);
        expect($songA->periods_on_chart)->toBe(2);
        expect($songA->peak_position)->toBe(1); // Keeps its peak
    });

    test('user dashboard shows latest chart data', function () {
        $user = User::factory()->create();

        // Create charts for multiple periods
        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 1,
            'track_name' => 'Old Hit',
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 2,
            'position' => 1,
            'track_name' => 'Current Hit',
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 2,
            'position' => 2,
            'track_name' => 'Second Current Hit',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');

        $chart = $response->viewData('chart');
        expect($chart)->toHaveCount(2); // Only period 2 charts
        expect($chart->pluck('track_name')->toArray())->toContain('Current Hit', 'Second Current Hit');
        expect($chart->pluck('track_name')->toArray())->not()->toContain('Old Hit');
    });

    test('public chart view shows correct chart runs data', function () {
        $user = User::factory()->create([
            'spotify_id' => 'public_user'
        ]);

        // Create chart progression for a track across multiple periods
        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'track_spotify_id' => 'track1',
            'position' => 5,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 2,
            'track_spotify_id' => 'track1',
            'position' => 3,
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 3,
            'track_spotify_id' => 'track1',
            'position' => 1,
        ]);

        $response = $this->get("/chart/public_user/3");

        $response->assertStatus(200);
        $response->assertViewIs('chart');

        $chart = $response->viewData('chart');
        $trackChart = $chart->where('track_spotify_id', 'track1')->first();

        expect($trackChart->chart_runs)->not()->toBeNull();

        // Parse chart runs and verify progression
        $chartRuns = json_decode($trackChart->chart_runs, true);
        expect($chartRuns)->toContain(5, 3, 1);
    });

    test('rewind functionality calculates year-end charts correctly', function () {
        $user = User::factory()->create();

        // Create charts throughout 2023
        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track1',
            'track_name' => 'Biggest Hit',
            'position' => 1,
            'created_at' => '2023-03-01',
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track1',
            'track_name' => 'Biggest Hit',
            'position' => 1,
            'created_at' => '2023-06-01',
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track2',
            'track_name' => 'Second Hit',
            'position' => 5,
            'created_at' => '2023-03-01',
        ]);

        $response = $this->actingAs($user)->get('/rewind/2023');

        $response->assertStatus(200);
        $chart = $response->viewData('chart');

        // Track 1 should be first (higher score: 2 weeks at #1)
        $topTrack = $chart->first();
        expect($topTrack->track_name)->toBe('Biggest Hit');
        expect($topTrack->weeks_on_no_1)->toBe(2);
        expect($topTrack->peak)->toBe(1);
        expect($topTrack->score)->toBe(40); // (21-1) * 2 = 40

        // Track 2 should be second
        $secondTrack = $chart->last();
        expect($secondTrack->track_name)->toBe('Second Hit');
        expect($secondTrack->weeks_on_no_1)->toBe(0);
        expect($secondTrack->peak)->toBe(5);
        expect($secondTrack->score)->toBe(16); // (21-5) * 1 = 16
    });
});
