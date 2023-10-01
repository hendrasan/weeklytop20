<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;

use Socialite;

use App\Models\User;
use App\Models\Chart;
use App\Services\Spotify;

class AuthSpotifyController extends Controller
{
    /**
     * Redirect the user to the Spotify authentication page
     *
     */
    public function spotifyLogin()
    {
        return Socialite::driver('spotify')
            ->scopes([
                'user-top-read',
                'playlist-modify-public',
                'playlist-modify-private'
            ])
            ->redirect();
    }

    public function spotifyCallback(Spotify $spotify)
    {
        $user = Socialite::driver('spotify')->user();

        $auth_user = $this->findOrCreateUser($user);

        Auth::login($auth_user);

        // if user doesn't have chart, generate one
        $user_charts = Chart::where('user_id', $auth_user->id)->exists();

        if (!$user_charts) {
            $spotify->generateChart($auth_user);
        }

        return redirect()->route('home');
    }

    public function findOrCreateUser($user)
    {
        $auth_user = User::where('spotify_id', $user->id)->first();

        if (!$auth_user) {
            // if it doesn't exist, create a new user
            $new_user = User::create([
                'name' => $user->name,
                'email' => !empty($user->email) ? $user->email : $user->name . '@spotify.com',
                'avatar' => !empty($user->avatar) ? $user->avatar : '',
                'spotify_id' => $user->id,
                'spotify_access_token' => $user->token,
                'spotify_refresh_token' => $user->refreshToken,
            ]);

            return $new_user;
        } else {
            // otherwise, update the access token and refresh token
            $auth_user->avatar = !empty($user->avatar) ? $user->avatar : '';
            $auth_user->spotify_access_token = $user->token;
            $auth_user->spotify_refresh_token = $user->refreshToken;

            $auth_user->save();

            return $auth_user;
        }
    }

    public function getLogout()
    {
        Auth::logout();
        return redirect()->route('home');
    }
}
