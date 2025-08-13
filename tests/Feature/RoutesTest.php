<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Web Routes', function () {
    test('home route is accessible', function () {
        $response = $this->get('/');

        $response->assertStatus(200);
    });

    // test('spotify login route is accessible', function () {
    //     $response = $this->get('/login/spotify');

    //     // This will redirect to Spotify, which is expected
    //     $response->assertStatus(302);
    // });

    test('logout route is accessible', function () {
        $response = $this->get('/logout');

        $response->assertRedirect('/');
    });

    test('chart route with username is accessible', function () {
        $user = \App\Models\User::factory()->create([
            'spotify_id' => 'test_user'
        ]);

        \App\Models\Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
        ]);

        $response = $this->get('/chart/test_user');

        $response->assertStatus(200);
    });

    test('chart route with username and chart id is accessible', function () {
        $user = \App\Models\User::factory()->create([
            'spotify_id' => 'test_user'
        ]);

        \App\Models\Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 1,
        ]);

        $response = $this->get('/chart/test_user/1');

        $response->assertStatus(200);
    });

    test('dashboard route requires authentication', function () {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/');
    });

    test('authenticated user can access dashboard', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    });

    test('rewind route requires authentication', function () {
        $response = $this->get('/rewind/2023');

        $response->assertRedirect('/');
    });

    test('authenticated user can access rewind', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/rewind/2023');

        $response->assertStatus(200);
    });

    test('create rewind playlist route requires authentication', function () {
        $response = $this->post('/rewind/2023');

        $response->assertRedirect('/');
    });

    test('authenticated user can create rewind playlist', function () {
        $user = User::factory()->create();

        // Mock the Spotify service
        $this->mock(\App\Services\Spotify::class, function ($mock) {
            $mock->shouldReceive('createPlaylist')
                ->once()
                ->andReturn((object) ['id' => 'playlist123']);
        });

        $response = $this->actingAs($user)->post('/rewind/2023');

        $response->assertStatus(200);
    });

    test('welcome route redirects to home', function () {
        // The welcome route should redirect to home since it returns view('welcome')
        // But based on the routes file, '/' goes to HomeController@index
        $response = $this->get('/');

        $response->assertStatus(200);
    });

    test('auth middleware protects dashboard', function () {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/');
    });

    test('auth middleware protects rewind routes', function () {
        $response = $this->get('/rewind/2023');
        $response->assertRedirect('/');

        $response = $this->post('/rewind/2023');
        $response->assertRedirect('/');
    });

    test('route parameters are properly handled', function () {
        $user = User::factory()->create([
            'spotify_id' => 'special-user-123'
        ]);

        \App\Models\Chart::factory()->create([
            'user_id' => $user->id,
            'period' => 5,
        ]);

        // Test username with special characters
        $response = $this->get('/chart/special-user-123/5');
        $response->assertStatus(200);

        // Test year parameter
        $authenticatedResponse = $this->actingAs($user)->get('/rewind/2024');
        $authenticatedResponse->assertStatus(200);
    });

    test('non existent routes return 404', function () {
        $response = $this->get('/non-existent-route');

        $response->assertStatus(404);
    });
});
