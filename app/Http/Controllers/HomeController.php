<?php namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

use App\Services\Spotify;

use App\Models\User;
use App\Models\Chart;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only('getDashboard');
    }

    public function index(Spotify $spotify)
    {
        // $users = User::has('charts')->with('latest_chart_top_tracks')->paginate(20);
        $users = User::with(['latest_chart', 'latest_chart_top_tracks'])->paginate(20);

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

        $chart = Chart::where('user_id', $user->id)
            // ->selectRaw('*, (SELECT JSON_ARRAYAGG(position) FROM charts c WHERE `user_id` = 1 and c.track_spotify_id = charts.track_spotify_id) as chart_runs')
            ->withChartRuns($user->id)
            ->where('period', $current_period)
            ->get();

        // $chart = $this_week_chart->map(function($c) use($current_period) {
        //     $chart_runs = Chart::where('track_spotify_id', $c->track_spotify_id)
        //         ->where('period', '<=', $current_period)
        //         // ->select('period', 'position', 'created_at')
        //         ->pluck('position')
        //         ->toArray();

        //     return $c->setAttribute('chart_runs', $chart_runs);
        // });

        return view('chart', compact('user', 'chart', 'latest_period', 'current_period'));
    }

    public function dashboard()
    {
        $user = Auth::user();

        $latest_period = Chart::where('user_id', $user->id)->max('period');

        $chart = Chart::where('user_id', $user->id)->where('period', $latest_period)->get();

        return view('dashboard', compact('user', 'chart'));
    }

    public function rewind($year)
    {
        $user = Auth::user();

        $subquery = Chart::query()
            ->select(
                'track_spotify_id',
                DB::raw('MIN(position) as peak'),
                DB::raw('SUM(CASE WHEN position = 1 THEN 1 ELSE 0 END) as weeks_on_no_1'),
                DB::raw('GROUP_CONCAT(position) as chart_runs'),
                DB::raw('COUNT(periods_on_chart) as total_periods_on_chart'),
                DB::raw('SUM(21 - position) as score')
            )
            ->where('user_id', $user->id)
            ->whereYear('created_at', $year)
            ->groupBy('track_spotify_id')
            ->orderByDesc('score')
            ->limit(50);

        $chart = Chart::where('user_id', $user->id)
            ->whereYear('created_at', $year)
            ->joinSub($subquery, 'aggs', function ($join) {
                $join->on('charts.track_spotify_id', '=', 'aggs.track_spotify_id');
            })
            ->select('charts.*', 'aggs.peak', 'aggs.weeks_on_no_1', 'aggs.chart_runs', 'aggs.total_periods_on_chart', 'aggs.score')
            ->whereIn('charts.id', function ($query) use($user, $year) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('charts')
                    ->where('user_id', $user->id)
                    ->whereYear('created_at', $year)
                    ->groupBy('track_spotify_id');
            })
            ->orderByDesc('aggs.score')
            ->take(50)
            ->get();

        return view('rewind', compact('year', 'chart'));
    }

    public function createRewindPlaylist(Spotify $spotify, $year)
    {
        $user = Auth::user();

        // $chart = Chart::where('user_id', $user->id)
        //     ->whereYear('created_at', $year)
        //     ->selectRaw('*, min(position) as peak, sum(case when position = 1 then 1 else 0 end) as weeks_on_no_1, group_concat(position) as chart_runs, COUNT(periods_on_chart) as total_periods_on_chart, SUM(21 - position) as score')
        //     ->groupBy('track_spotify_id')
        //     ->orderBy('score', 'desc')
        //     ->take(50)
        //     ->get();

        $subquery = Chart::query()
            ->select(
                'track_spotify_id',
                DB::raw('MIN(position) as peak'),
                DB::raw('SUM(CASE WHEN position = 1 THEN 1 ELSE 0 END) as weeks_on_no_1'),
                DB::raw('GROUP_CONCAT(position) as chart_runs'),
                DB::raw('COUNT(periods_on_chart) as total_periods_on_chart'),
                DB::raw('SUM(21 - position) as score')
            )
            ->where('user_id', $user->id)
            ->whereYear('created_at', $year)
            ->groupBy('track_spotify_id')
            ->orderByDesc('score')
            ->limit(50);

        $chart = Chart::where('user_id', $user->id)
            ->whereYear('created_at', $year)
            ->joinSub($subquery, 'aggs', function ($join) {
                $join->on('charts.track_spotify_id', '=', 'aggs.track_spotify_id');
            })
            ->select('charts.*', 'aggs.peak', 'aggs.weeks_on_no_1', 'aggs.chart_runs', 'aggs.total_periods_on_chart', 'aggs.score')
            ->whereIn('charts.id', function ($query) use ($user, $year) {
                $query->select(DB::raw('MIN(id)'))
                ->from('charts')
                    ->where('user_id', $user->id)
                    ->whereYear('created_at', $year)
                    ->groupBy('track_spotify_id');
            })
            ->orderByDesc('aggs.score')
            ->take(50)
            ->get();

        $tracks = $chart->pluck('track_spotify_id')->toArray();

        $new_playlist = $spotify->createPlaylist($user, [
            'title' => 'Your Top Songs in ' . $year,
            'tracks' => $tracks
        ]);

        return response()->json($new_playlist);
    }
}
