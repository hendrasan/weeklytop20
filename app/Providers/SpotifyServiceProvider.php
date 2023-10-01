<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use SpotifyWebAPI\SpotifyWebAPI;

use Illuminate\Support\Facades\Auth;

class SpotifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SpotifyWebAPI::class, function ($app) {
            $client = new SpotifyWebAPI;

            if ($auth_user = Auth::user()) {
                $accessToken = $auth_user->spotify_access_token;
                $client->setAccessToken($accessToken);
            }

            return $client;
        });
    }
}
