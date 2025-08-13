<?php

use App\Models\Chart;
use App\Models\User;
use App\Services\Spotify;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPIException;

uses(RefreshDatabase::class);

describe('Spotify Service', function () {
    beforeEach(function () {
        $this->spotifyApiMock = Mockery::mock(SpotifyWebAPI::class);
        $this->spotifyService = new Spotify($this->spotifyApiMock);
        $this->user = User::factory()->create([
            'spotify_access_token' => 'test_access_token',
            'spotify_refresh_token' => 'test_refresh_token',
        ]);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('getChart sets access token and retrieves top tracks', function () {
        $mockTracks = (object) [
            'items' => [
                (object) [
                    'id' => 'track1',
                    'name' => 'Song 1',
                    'artists' => [(object) ['name' => 'Artist 1']]
                ],
                (object) [
                    'id' => 'track2',
                    'name' => 'Song 2',
                    'artists' => [(object) ['name' => 'Artist 2']]
                ]
            ]
        ];

        $this->spotifyApiMock
            ->shouldReceive('setAccessToken')
            ->once()
            ->with($this->user->spotify_access_token);

        $this->spotifyApiMock
            ->shouldReceive('getMyTop')
            ->once()
            ->with('tracks', [
                'limit' => 20,
                'time_range' => 'short_term'
            ])
            ->andReturn($mockTracks);

        $result = $this->spotifyService->getChart($this->user);

        expect($result)->toBe($mockTracks);
    });

    test('generateChart creates first chart for new user', function () {
        $mockTracks = (object) [
            'items' => [
                (object) [
                    'id' => 'track1',
                    'name' => 'Song 1',
                    'artists' => [(object) ['name' => 'Artist 1']]
                ],
                (object) [
                    'id' => 'track2',
                    'name' => 'Song 2',
                    'artists' => [(object) ['name' => 'Artist 2']]
                ]
            ]
        ];

        $this->spotifyApiMock
            ->shouldReceive('setAccessToken')
            ->once()
            ->with($this->user->spotify_access_token);

        $this->spotifyApiMock
            ->shouldReceive('getMyTop')
            ->once()
            ->andReturn($mockTracks);

        Carbon::setTestNow('2023-01-01 12:00:00');

        $result = $this->spotifyService->generateChart($this->user);

        expect($result)->toBeTrue();

        $charts = Chart::where('user_id', $this->user->id)->get();
        expect($charts)->toHaveCount(2);

        $firstChart = $charts->where('position', 1)->first();
        expect($firstChart->track_spotify_id)->toBe('track1');
        expect($firstChart->track_name)->toBe('Song 1');
        expect($firstChart->track_artist)->toBe('Artist 1');
        expect($firstChart->period)->toBe(1);
        expect($firstChart->position)->toBe(1);
        expect($firstChart->periods_on_chart)->toBe(1);
        expect($firstChart->peak_position)->toBe(1);
        expect($firstChart->last_position)->toBeNull();
        expect($firstChart->is_reentry)->toBeNull();

        Carbon::setTestNow(); // Reset Carbon
    });

    test('generateChart does not create new chart if latest chart is less than 2 days old', function () {
        // Create a recent chart
        Chart::factory()->create([
            'user_id' => $this->user->id,
            'period' => 1,
            'created_at' => now()->subDay(), // 1 day ago
        ]);

        $this->spotifyApiMock
            ->shouldReceive('setAccessToken')
            ->once()
            ->with($this->user->spotify_access_token);

        $this->spotifyApiMock
            ->shouldReceive('getMyTop')
            ->once()
            ->andReturn((object) ['items' => []]);

        $result = $this->spotifyService->generateChart($this->user);

        expect($result)->toBeNull();
        expect(Chart::where('user_id', $this->user->id)->count())->toBe(1);
    });

    test('generateChart creates new period chart when latest chart is older than 2 days', function () {
        // Create an old chart
        Chart::factory()->create([
            'user_id' => $this->user->id,
            'period' => 1,
            'position' => 1,
            'track_spotify_id' => 'track1',
            'created_at' => now()->subDays(3),
        ]);

        $mockTracks = (object) [
            'items' => [
                (object) [
                    'id' => 'track1', // Same track from previous period
                    'name' => 'Song 1',
                    'artists' => [(object) ['name' => 'Artist 1']]
                ],
                (object) [
                    'id' => 'track2', // New track
                    'name' => 'Song 2',
                    'artists' => [(object) ['name' => 'Artist 2']]
                ]
            ]
        ];

        $this->spotifyApiMock
            ->shouldReceive('setAccessToken')
            ->once()
            ->with($this->user->spotify_access_token);

        $this->spotifyApiMock
            ->shouldReceive('getMyTop')
            ->once()
            ->andReturn($mockTracks);

        $result = $this->spotifyService->generateChart($this->user);

        expect($result)->toBeTrue();

        $newCharts = Chart::where('user_id', $this->user->id)
            ->where('period', 2)
            ->get();

        expect($newCharts)->toHaveCount(2);

        $track1Chart = $newCharts->where('track_spotify_id', 'track1')->first();
        expect($track1Chart->last_position)->toBe(1); // Previous position
        expect($track1Chart->periods_on_chart)->toBe(2);
        expect($track1Chart->is_reentry)->toBeNull();

        $track2Chart = $newCharts->where('track_spotify_id', 'track2')->first();
        expect($track2Chart->last_position)->toBeNull(); // New track
        expect($track2Chart->periods_on_chart)->toBe(1);
        expect($track2Chart->is_reentry)->toBeNull();
    });

    test('generateChart marks track as reentry when it returns after absence', function () {
        $user = User::factory()->create();

        // Create charts for period 1
        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'track_spotify_id' => 'track1',
            'position' => 5,
            'created_at' => now()->subDays(10),
        ]);

        // Create charts for period 2 (track1 is absent)
        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 2,
            'track_spotify_id' => 'track2',
            'position' => 1,
            'created_at' => now()->subDays(5),
        ]);

        $mockTracks = (object) [
            'items' => [
                (object) [
                    'id' => 'track1', // Returning track
                    'name' => 'Song 1',
                    'artists' => [(object) ['name' => 'Artist 1']]
                ]
            ]
        ];

        $this->spotifyApiMock
            ->shouldReceive('setAccessToken')
            ->once()
            ->with($user->spotify_access_token);

        $this->spotifyApiMock
            ->shouldReceive('getMyTop')
            ->once()
            ->andReturn($mockTracks);

        $spotifyService = new Spotify($this->spotifyApiMock);
        $result = $spotifyService->generateChart($user);

        expect($result)->toBeTrue();

        $reentryChart = Chart::where('user_id', $user->id)
            ->where('period', 3)
            ->where('track_spotify_id', 'track1')
            ->first();

        expect($reentryChart->is_reentry)->toBe(1);
        expect($reentryChart->periods_on_chart)->toBe(2); // Was on chart before
    });

    test('createPlaylist creates spotify playlist with tracks', function () {
        $mockPlaylist = (object) [
            'id' => 'playlist123',
            'name' => 'Test Playlist'
        ];

        $payload = [
            'title' => 'My Weekly Top 20',
            'tracks' => ['track1', 'track2', 'track3']
        ];

        $this->spotifyApiMock
            ->shouldReceive('createPlaylist')
            ->once()
            ->with($this->user->spotify_id, [
                'name' => 'My Weekly Top 20'
            ])
            ->andReturn($mockPlaylist);

        $this->spotifyApiMock
            ->shouldReceive('addPlaylistTracks')
            ->once()
            ->with('playlist123', ['track1', 'track2', 'track3']);

        $result = $this->spotifyService->createPlaylist($this->user, $payload);

        expect($result)->toBe($mockPlaylist);
    });

    test('createPlaylist returns false when no tracks provided', function () {
        $payload = [
            'title' => 'Empty Playlist',
            'tracks' => []
        ];

        $result = $this->spotifyService->createPlaylist($this->user, $payload);

        expect($result)->toBeFalse();
    });

    test('createPlaylist uses default title when none provided', function () {
        $mockPlaylist = (object) [
            'id' => 'playlist123',
            'name' => 'Your Weekly Top 20'
        ];

        $payload = [
            'tracks' => ['track1', 'track2']
        ];

        $this->spotifyApiMock
            ->shouldReceive('createPlaylist')
            ->once()
            ->with($this->user->spotify_id, [
                'name' => 'Your Weekly Top 20'
            ])
            ->andReturn($mockPlaylist);

        $this->spotifyApiMock
            ->shouldReceive('addPlaylistTracks')
            ->once()
            ->with('playlist123', ['track1', 'track2']);

        $result = $this->spotifyService->createPlaylist($this->user, $payload);

        expect($result)->toBe($mockPlaylist);
    });

    test('generateChart refreshes token when access token expires', function () {
        $mockTracks = (object) [
            'items' => [
                (object) [
                    'id' => 'track1',
                    'name' => 'Song 1',
                    'artists' => [(object) ['name' => 'Artist 1']]
                ]
            ]
        ];

        // Mock config values
        config([
            'services.spotify.client_id' => 'test_client_id',
            'services.spotify.client_secret' => 'test_client_secret',
            'services.spotify.redirect' => 'http://localhost/callback'
        ]);

        $this->spotifyApiMock
            ->shouldReceive('setAccessToken')
            ->once()
            ->with($this->user->spotify_access_token);

        $this->spotifyApiMock
            ->shouldReceive('setAccessToken')
            ->once()
            ->with('new_access_token');

        $this->spotifyApiMock
            ->shouldReceive('getMyTop')
            ->once()
            ->andThrow(new SpotifyWebAPIException('The access token expired'));

        $this->spotifyApiMock
            ->shouldReceive('getMyTop')
            ->once()
            ->andReturn($mockTracks);

        // Mock Session for token refresh
        $sessionMock = Mockery::mock('overload:' . Session::class);
        $sessionMock
            ->shouldReceive('__construct')
            ->once()
            ->with('test_client_id', 'test_client_secret', 'http://localhost/callback');

        $sessionMock
            ->shouldReceive('refreshAccessToken')
            ->once()
            ->with($this->user->spotify_refresh_token);

        $sessionMock
            ->shouldReceive('getAccessToken')
            ->once()
            ->andReturn('new_access_token');

        $sessionMock
            ->shouldReceive('getRefreshToken')
            ->once()
            ->andReturn('new_refresh_token');

        $result = $this->spotifyService->generateChart($this->user);

        // Refresh the user model to see updated tokens
        $this->user->refresh();

        // Now that the bug is fixed, the method should return true
        expect($result)->toBeTrue();
        expect($this->user->spotify_access_token)->toBe('new_access_token');
        expect($this->user->spotify_refresh_token)->toBe('new_refresh_token');

        // Verify that charts were actually created after token refresh
        $charts = Chart::where('user_id', $this->user->id)->get();
        expect($charts)->toHaveCount(1);
    });
});
