<?php

use App\Http\Controllers\AuthSpotifyController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RewindController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthSpotifyController::class)->group(function () {
    Route::get('/login/spotify', 'spotifyLogin')->name('login.spotify');
    Route::get('/auth/spotify', 'spotifyCallback');

    Route::get('/logout', 'getLogout')->name('logout');
});

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home');
});

Route::controller(ChartController::class)->group(function () {
    Route::get('chart/{user}/{chartId?}', 'chart')->name('chart');
});

Route::middleware(['auth'])->group(function () {
    Route::controller(DashboardController::class)->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
    });
    Route::controller(RewindController::class)->group(function () {
        Route::get('rewind/{year}', 'rewind')->name('rewind');
        Route::post('rewind/{year}', 'createRewindPlaylist')->name('rewind.create-playlist');
    });
});
