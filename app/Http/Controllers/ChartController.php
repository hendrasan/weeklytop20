<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Chart;
use Illuminate\Http\Request;

class ChartController extends Controller
{
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
            ->withChartRuns($user->id)
            ->where('period', $current_period)
            ->get();

        return view('chart', compact('user', 'chart', 'latest_period', 'current_period'));
    }
}
