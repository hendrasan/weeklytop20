<?php

use App\Jobs\GenerateChart;
use App\Models\User;
use App\Services\Spotify;

it('calls spotify->generateChart() with the correct user', function () {
    $user = User::factory()->create();

    $client = Mockery::mock(Spotify::class);
    $client->shouldReceive('generateChart')
        ->once()
        ->withArgs(function (User $u) use ($user) {
            return $u->id === $user->id;
        });

    $job = new GenerateChart($user->id);
    $job->handle($client);
});

it('logs an error if the user is not found', function () {
    $client = Mockery::mock(Spotify::class);
    $client->shouldNotReceive('generateChart');

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message) {
            return str_contains($message, 'User not found');
        });

    $job = new GenerateChart(9999);
    $job->handle($client);
});