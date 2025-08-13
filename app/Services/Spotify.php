<?php

namespace App\Services;

use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPIException;

use Log;
use Carbon\Carbon;

use App\Models\Chart;

class Spotify {
    public function __construct(
        protected SpotifyWebAPI $spotify
    ) {}

    public function getChart($user)
    {
        try {
            $this->spotify->setAccessToken($user->spotify_access_token);

            $top20_tracks = $this->spotify->getMyTop('tracks', [
                'limit' => 20,
                'time_range' => 'short_term' // long_term | medium_term | short_term
            ]);

            return $top20_tracks;
        } catch (Exception $e) {
            Log::info($e);
        }
    }

    public function generateChart($user)
    {
        try {
            $this->spotify->setAccessToken($user->spotify_access_token);

            $top20_tracks = $this->spotify->getMyTop('tracks', [
                'limit' => 20,
                'time_range' => 'short_term' // long_term | medium_term | short_term
            ]);

            $now = Carbon::now();
            $now_timestamp = $now->toDateTimeString();

            $latest_chart = Chart::where('user_id', $user->id)
                ->latest()
                ->first();

            if (!empty($latest_chart)) {
                $latest_chart_at = Carbon::createFromFormat('Y-m-d H:i:s', $latest_chart->created_at);

                // don't generate new chart if the latest chart is under 2 days old
                if ($now->diffInDays($latest_chart_at) < 2) {
                    return;
                }

                // generate chart
                $new_chart = [];
                $current_chart = Chart::where('user_id', $user->id)
                    ->where('period', $latest_chart->period)
                    ->get();

                foreach ($top20_tracks->items as $key => $item) {
                    $track_current_chart = $current_chart->firstWhere('track_spotify_id', $item->id);
                    $track_current_position = $key + 1;
                    $track_peak_position = Chart::where('user_id', $user->id)
                        ->where('track_spotify_id', $item->id)
                        ->min('position');
                    if (empty($track_peak_position) || $track_current_position < $track_peak_position) {
                        $track_peak_position = $track_current_position;
                    }

                    $periods_on_chart = Chart::where('user_id', $user->id)
                        ->where('track_spotify_id', $item->id)
                        ->count();

                    // $is_reentry is true if the track is not on the latest chart but has been on the chart before
                    $is_reentry = empty($track_current_chart) && Chart::where('user_id', $user->id)->where('track_spotify_id', $item->id)->count() > 0 ? 1 : null;

                    $pushed_item = [
                        'user_id'          => $user->id,
                        'period'           => $latest_chart->period + 1,
                        'track_spotify_id' => $item->id,
                        'track_name'       => $item->name,
                        'track_artist'     => $item->artists[0]->name,
                        'track_data'       => json_encode($item),
                        'position'         => $track_current_position,
                        'last_position'    => $track_current_chart->position ?? null,
                        'periods_on_chart' => $periods_on_chart + 1,
                        'peak_position'    => $track_peak_position,
                        'is_reentry'       => $is_reentry,
                        'created_at'       => $now_timestamp,
                        'updated_at'       => $now_timestamp
                    ];

                    array_push($new_chart, $pushed_item);
                }

                $chart = Chart::insert($new_chart);
            } else {
                // otherwise, create a chart
                $new_chart = [];
                foreach ($top20_tracks->items as $key => $item) {
                    $pushed_item = array(
                        'user_id'          => $user->id,
                        'period'           => 1,
                        'track_spotify_id' => $item->id,
                        'track_name'       => $item->name,
                        'track_artist'     => $item->artists[0]->name,
                        'track_data'       => json_encode($item),
                        'position'         => $key + 1,
                        'last_position'    => null,
                        'periods_on_chart' => 1,
                        'peak_position'    => $key + 1,
                        'is_reentry'       => null,
                        'created_at'       => $now_timestamp,
                        'updated_at'       => $now_timestamp
                    );

                    array_push($new_chart, $pushed_item);
                }

                $chart = Chart::insert($new_chart);
            }

            return true;
        }
        catch(Exception $e) {
            Log::info($e);
        }
        catch(SpotifyWebAPIException $e) {
            if ($e->getMessage() == "The access token expired") {
                Log::info("Access token expired. Refreshing token...");

                $session = new Session(
                    config('services.spotify.client_id'),
                    config('services.spotify.client_secret'),
                    config('services.spotify.redirect')
                );

                $session->refreshAccessToken($user->spotify_refresh_token);

                $user->spotify_access_token = $session->getAccessToken();
                $user->spotify_refresh_token = $session->getRefreshToken();
                $user->save();

                return $this->generateChart($user);
            }

            Log::info($e);
        }

    }

    public function createPlaylist($user, $payload)
    {
        try {
            $playlist_name = $payload['title'] ?? 'Your Weekly Top 20';
            $tracks = $payload['tracks'] ?? [];

            if (empty($tracks)) {
                return false;
            }

            $new_playlist = $this->spotify->createPlaylist($user->spotify_id, [
                'name' => $playlist_name
            ]);

            $this->spotify->addPlaylistTracks($new_playlist->id, $tracks);

            return $new_playlist;
        }
        catch(SpotifyWebAPIException $e) {
            Log::info($e);
            // if ($request->ajax()) {
            //     return response()->json([
            //         'status' => $e->getCode(),
            //         'message' => $e->getMessage()
            //     ]);
            // } else {
            //     return redirect('/');
            // }
        }
    }
}
