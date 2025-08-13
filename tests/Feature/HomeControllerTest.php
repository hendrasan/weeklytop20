<?php

use App\Models\Chart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Home Controller', function () {
    test('index page displays users with charts', function () {
        // Create users with charts
        $userWithChart = User::factory()->create();
        Chart::factory()->create([
            'user_id' => $userWithChart->id,
            'period' => 1,
            'position' => 1,
        ]);

        // Create user without charts (should not appear on index)
        User::factory()->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('index');
        $response->assertViewHas('users');
    });

    test('chart page displays user chart for specific period', function () {
        $user = User::factory()->create([
            'spotify_id' => 'test_user_123'
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 1,
            'track_name' => 'Song 1',
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 2,
            'track_name' => 'Song 2',
        ]);

        $response = $this->get("/chart/{$user->spotify_id}/1");

        $response->assertStatus(200);
        $response->assertViewIs('chart');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('chart');
        $response->assertViewHas('current_period', 1);
        $response->assertViewHas('latest_period', 1);
    });

    test('chart page redirects to latest period when period is too high', function () {
        $user = User::factory()->create([
            'spotify_id' => 'test_user_123'
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 1,
        ]);

        // Try to access period 5 when only period 1 exists
        $response = $this->get("/chart/{$user->spotify_id}/5");

        $response->assertRedirect("/chart/{$user->spotify_id}");
    });

    test('chart page shows latest period when no period specified', function () {
        $user = User::factory()->create([
            'spotify_id' => 'test_user_123'
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 2,
            'position' => 1,
        ]);

        $response = $this->get("/chart/{$user->spotify_id}");

        $response->assertStatus(200);
        $response->assertViewHas('current_period', 2);
        $response->assertViewHas('latest_period', 2);
    });

    test('chart page returns nothing for non-existent user', function () {
        $response = $this->get('/chart/nonexistent_user');

        // The controller returns nothing (void) for non-existent users
        // This might result in a 404 or empty response depending on Laravel's handling
        expect($response->getContent())->toBe('');
    });

    test('dashboard requires authentication', function () {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/');
    });

    test('dashboard shows authenticated user chart', function () {
        $user = User::factory()->create();

        Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
            'position' => 1,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('chart');
    });

    test('rewind shows year end chart for authenticated user', function () {
        $user = User::factory()->create();

        // Create charts for 2023
        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track1',
            'position' => 1,
            'created_at' => '2023-06-01 12:00:00',
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track1',
            'position' => 2,
            'created_at' => '2023-07-01 12:00:00',
        ]);

        $response = $this->actingAs($user)->get('/rewind/2023');

        $response->assertStatus(200);
        $response->assertViewIs('rewind');
        $response->assertViewHas('year', 2023);
        $response->assertViewHas('chart');
    });

    test('rewind requires authentication', function () {
        $response = $this->get('/rewind/2023');

        $response->assertRedirect('/');
    });

    test('createRewindPlaylist requires authentication', function () {
        $response = $this->post('/rewind/2023');

        $response->assertRedirect('/');
    });

    test('createRewindPlaylist returns json response', function () {
        $user = User::factory()->create();

        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track1',
            'created_at' => '2023-06-01 12:00:00',
        ]);

        // Mock the Spotify service
        $this->mock(\App\Services\Spotify::class, function ($mock) {
            $mock->shouldReceive('createPlaylist')
                ->once()
                ->andReturn((object) [
                    'id' => 'playlist123',
                    'name' => 'Your Top Songs in 2023'
                ]);
        });

        $response = $this->actingAs($user)->post('/rewind/2023');

        $response->assertStatus(200);
        $response->assertJson([
            'id' => 'playlist123',
            'name' => 'Your Top Songs in 2023'
        ]);
    });

    test('rewind calculates correct track scores and ordering', function () {
        $user = User::factory()->create();

        // Track 1: Higher score (appears at position 1 twice)
        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track1',
            'track_name' => 'Best Song',
            'position' => 1, // Score: 21-1 = 20
            'created_at' => '2023-06-01 12:00:00',
        ]);

        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track1',
            'track_name' => 'Best Song',
            'position' => 1, // Score: 21-1 = 20
            'created_at' => '2023-07-01 12:00:00',
        ]);

        // Track 2: Lower score (appears at position 5 once)
        Chart::factory()->create([
            'user_id' => $user->id,
            'track_spotify_id' => 'track2',
            'track_name' => 'Good Song',
            'position' => 5, // Score: 21-5 = 16
            'created_at' => '2023-06-01 12:00:00',
        ]);

        $response = $this->actingAs($user)->get('/rewind/2023');

        $response->assertStatus(200);

        $chart = $response->viewData('chart');

        // Verify tracks are ordered by score (highest first)
        expect($chart->first()->track_name)->toBe('Best Song');
        expect($chart->first()->score)->toBe(40); // 20 + 20
        expect($chart->first()->peak)->toBe(1);
        expect($chart->first()->weeks_on_no_1)->toBe(2);

        expect($chart->last()->track_name)->toBe('Good Song');
        expect($chart->last()->score)->toBe(16);
        expect($chart->last()->peak)->toBe(5);
        expect($chart->last()->weeks_on_no_1)->toBe(0);
    });
});
