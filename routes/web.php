<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::controller(AuthSpotifyController::class)->group(function () {
    Route::get('/login/spotify', 'spotifyLogin')->name('login.spotify');
    Route::get('/auth/spotify', 'spotifyCallback');

    Route::get('/logout', 'getLogout')->name('logout');
});

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home');

    Route::get('chart/{user}/{chartId?}', 'chart')->name('chart');

    Route::middleware(['auth'])->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard')->middleware('auth');

        Route::get('rewind/{year}', 'rewind')->name('rewind')->middleware('auth');
        Route::post('rewind/{year}', 'createRewindPlaylist')->name('rewind.create-playlist')->middleware('auth');
    });
});
