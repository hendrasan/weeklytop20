<?php

use App\Models\Chart;
use App\Models\User;
use App\Services\Spotify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

describe('Spotify Authentication Controller', function () {
    afterEach(function () {
        Mockery::close();
    });

    test('spotifyLogin redirects to Spotify with correct scopes', function () {
        // Mock Laravel Socialite
        $socialiteMock = Mockery::mock('alias:Laravel\Socialite\Facades\Socialite');
        $driverMock = Mockery::mock();

        $socialiteMock
            ->shouldReceive('driver')
            ->with('spotify')
            ->andReturn($driverMock);

        $driverMock
            ->shouldReceive('scopes')
            ->with([
                'user-top-read',
                'playlist-modify-public',
                'playlist-modify-private'
            ])
            ->andReturn($driverMock);

        $driverMock
            ->shouldReceive('redirect')
            ->andReturn(redirect('https://spotify.com/auth'));

        $response = $this->get('/login/spotify');

        $response->assertRedirect();
    });

    test('spotifyCallback creates new user and generates chart', function () {
        // Mock Socialite user
        $spotifyUser = (object) [
            'id' => 'spotify123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
            'token' => 'access_token_123',
            'refreshToken' => 'refresh_token_123',
        ];

        // Mock Socialite
        $socialiteMock = Mockery::mock('alias:Laravel\Socialite\Facades\Socialite');
        $driverMock = Mockery::mock();

        $socialiteMock
            ->shouldReceive('driver')
            ->with('spotify')
            ->andReturn($driverMock);

        $driverMock
            ->shouldReceive('user')
            ->andReturn($spotifyUser);

        // Mock Spotify service
        $spotifyMock = Mockery::mock(Spotify::class);
        $spotifyMock
            ->shouldReceive('generateChart')
            ->once()
            ->with(Mockery::type(User::class));

        $this->app->instance(Spotify::class, $spotifyMock);

        $response = $this->get('/auth/spotify');

        $response->assertRedirect('/');

        // Verify user was created
        $user = User::where('spotify_id', 'spotify123')->first();
        expect($user)->not()->toBeNull();
        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');
        expect($user->spotify_access_token)->toBe('access_token_123');
        expect($user->spotify_refresh_token)->toBe('refresh_token_123');

        // Verify user is authenticated
        $this->assertAuthenticatedAs($user);
    });

    test('spotifyCallback updates existing user tokens', function () {
        // Create existing user
        $existingUser = User::factory()->create([
            'spotify_id' => 'spotify123',
            'name' => 'John Doe',
            'spotify_access_token' => 'old_access_token',
            'spotify_refresh_token' => 'old_refresh_token',
        ]);

        // Create existing chart
        Chart::factory()->create(['user_id' => $existingUser->id]);

        // Mock Socialite user
        $spotifyUser = (object) [
            'id' => 'spotify123',
            'name' => 'John Doe Updated',
            'email' => 'john.updated@example.com',
            'avatar' => 'https://example.com/new_avatar.jpg',
            'token' => 'new_access_token',
            'refreshToken' => 'new_refresh_token',
        ];

        // Mock Socialite
        $socialiteMock = Mockery::mock('alias:Laravel\Socialite\Facades\Socialite');
        $driverMock = Mockery::mock();

        $socialiteMock
            ->shouldReceive('driver')
            ->with('spotify')
            ->andReturn($driverMock);

        $driverMock
            ->shouldReceive('user')
            ->andReturn($spotifyUser);

        // Mock Spotify service - should NOT be called since user already has charts
        $spotifyMock = Mockery::mock(Spotify::class);
        $spotifyMock
            ->shouldNotReceive('generateChart');

        $this->app->instance(Spotify::class, $spotifyMock);

        $response = $this->get('/auth/spotify');

        $response->assertRedirect('/');

        // Verify user was updated
        $existingUser->refresh();
        expect($existingUser->spotify_access_token)->toBe('new_access_token');
        expect($existingUser->spotify_refresh_token)->toBe('new_refresh_token');
        expect($existingUser->avatar)->toBe('https://example.com/new_avatar.jpg');

        // Verify user is authenticated
        $this->assertAuthenticatedAs($existingUser);
    });

    test('spotifyCallback handles user without email', function () {
        // Mock Socialite user without email
        $spotifyUser = (object) [
            'id' => 'spotify123',
            'name' => 'John Doe',
            'email' => null,
            'avatar' => null,
            'token' => 'access_token_123',
            'refreshToken' => 'refresh_token_123',
        ];

        // Mock Socialite
        $socialiteMock = Mockery::mock('alias:Laravel\Socialite\Facades\Socialite');
        $driverMock = Mockery::mock();

        $socialiteMock
            ->shouldReceive('driver')
            ->with('spotify')
            ->andReturn($driverMock);

        $driverMock
            ->shouldReceive('user')
            ->andReturn($spotifyUser);

        // Mock Spotify service
        $spotifyMock = Mockery::mock(Spotify::class);
        $spotifyMock
            ->shouldReceive('generateChart')
            ->once();

        $this->app->instance(Spotify::class, $spotifyMock);

        $response = $this->get('/auth/spotify');

        $response->assertRedirect('/');

        // Verify user was created with generated email
        $user = User::where('spotify_id', 'spotify123')->first();
        expect($user->email)->toBe('John Doe@spotify.com');
        expect($user->avatar)->toBe(null);
    });

    test('findOrCreateUser creates new user when not exists', function () {
        $spotifyUser = (object) [
            'id' => 'spotify_new_user',
            'name' => 'New User',
            'email' => 'new@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
            'token' => 'access_token',
            'refreshToken' => 'refresh_token',
        ];

        $controller = new \App\Http\Controllers\AuthSpotifyController();
        $user = $controller->findOrCreateUser($spotifyUser);

        expect($user)->toBeInstanceOf(User::class);
        expect($user->spotify_id)->toBe('spotify_new_user');
        expect($user->name)->toBe('New User');
        expect($user->email)->toBe('new@example.com');
        expect($user->wasRecentlyCreated)->toBeTrue();
    });

    test('findOrCreateUser updates existing user', function () {
        $existingUser = User::factory()->create([
            'spotify_id' => 'spotify_existing',
            'spotify_access_token' => 'old_token',
        ]);

        $spotifyUser = (object) [
            'id' => 'spotify_existing',
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'avatar' => 'https://example.com/new_avatar.jpg',
            'token' => 'new_access_token',
            'refreshToken' => 'new_refresh_token',
        ];

        $controller = new \App\Http\Controllers\AuthSpotifyController();
        $user = $controller->findOrCreateUser($spotifyUser);

        expect($user->id)->toBe($existingUser->id);
        expect($user->spotify_access_token)->toBe('new_access_token');
        expect($user->spotify_refresh_token)->toBe('new_refresh_token');
        expect($user->avatar)->toBe('https://example.com/new_avatar.jpg');
        expect($user->wasRecentlyCreated)->toBeFalse();
    });

    test('logout logs out user and redirects', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    });

    test('logout works for guest users', function () {
        $response = $this->get('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    });
});
