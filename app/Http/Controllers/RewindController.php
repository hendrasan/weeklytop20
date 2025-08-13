<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Chart;
use App\Services\Spotify;


use App\Services\ChartService;

class RewindController extends Controller
{
    protected $chartService;

    public function __construct(ChartService $chartService)
    {
        $this->chartService = $chartService;
    }

    public function rewind($year)
    {
        $user = Auth::user();
        $chart = $this->chartService->getUserChartWithAggregates($user, $year);
        return view('rewind', compact('year', 'chart'));
    }


    public function createRewindPlaylist(Spotify $spotify, $year)
    {
        $user = Auth::user();
        $chart = $this->chartService->getUserChartWithAggregates($user, $year);
        $tracks = $chart->pluck('track_spotify_id')->toArray();
        $new_playlist = $spotify->createPlaylist($user, [
            'title' => 'Your Top Songs in ' . $year,
            'tracks' => $tracks
        ]);
        return response()->json($new_playlist);
    }
}
