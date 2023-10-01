<?php namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

use App\Services\Spotify;

use App\Models\User;
use App\Models\Chart;

class HomeController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth')->only('getDashboard');
  }

  public function index(Spotify $spotify)
  {
    $users = User::has('charts')->paginate(20);

    return view('index', compact('users'));
  }

  public function chart($username, $chart_id = null)
  {
    $user = User::where('spotify_id', $username)->first();

    if (empty($user)) {
      return;
    }

    $latest_period = $user->charts()->max('period');

    $current_period = $chart_id ?? $latest_period;

    if ($current_period > $latest_period) {
      return redirect()->route('chart', [$user->spotify_id]);
    }

    $this_week_chart = Chart::where('user_id', $user->id)->where('period', $current_period)->get();

    $chart = $this_week_chart->map(function($c) use($current_period) {
      $chart_runs = Chart::where('track_spotify_id', $c->track_spotify_id)
        ->where('period', '<=', $current_period)
        // ->select('period', 'position', 'created_at')
        ->pluck('position')
        ->toArray();

      return $c->setAttribute('chart_runs', $chart_runs);
    });

    return view('chart', compact('user', 'chart', 'latest_period', 'current_period'));
  }

  public function dashboard()
  {
    $user = Auth::user();

    $latest_period = Chart::where('user_id', $user->id)
      ->max('period');

    $chart = Chart::where('user_id', $user->id)->where('period', $latest_period)->get();

    return view('dashboard', compact('user', 'chart'));
  }

  public function getRewind($year)
  {
    $user = Auth::user();

    $chart = Chart::where('user_id', $user->id)
                              ->whereYear('created_at', $year)
                              ->selectRaw('*, min(position) as peak, sum(case when position = 1 then 1 else 0 end) as weeks_on_no_1, group_concat(position) as chart_runs, COUNT(periods_on_chart) as total_periods_on_chart, SUM(21 - position) as score')
                              ->groupBy('track_spotify_id')
                              ->orderBy('score', 'desc')
                              ->take(50)
                              ->get();

    return view('rewind', compact('user', 'chart'));
  }

  public function postRewind(Spotify $spotify, $year)
  {
    $user = Auth::user();

    $chart = Chart::where('user_id', $user->id)
                          ->whereYear('created_at', $year)
                          ->selectRaw('*, min(position) as peak, sum(case when position = 1 then 1 else 0 end) as weeks_on_no_1, group_concat(position) as chart_runs, COUNT(periods_on_chart) as total_periods_on_chart, SUM(21 - position) as score')
                          ->groupBy('track_spotify_id')
                          ->orderBy('score', 'desc')
                          ->take(50)
                          ->get();

    $tracks = $chart->pluck('track_spotify_id')->toArray();

    $new_playlist = $spotify->createPlaylist($user, [
      'title' => 'Your Top Songs ' . $year,
      'tracks' => $tracks
    ]);

    return response()->json($new_playlist);
  }
}
