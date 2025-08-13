<?php
namespace App\Services;

use App\Models\Chart;
use Illuminate\Support\Facades\DB;

class ChartService
{
    /**
     * Get chart data with aggregates for a user and year.
     */
    public function getUserChartWithAggregates($user, $year)
    {
        $subquery = Chart::query()
            ->select(
                'track_spotify_id',
                DB::raw('MIN(position) as peak'),
                DB::raw('CAST(SUM(CASE WHEN position = 1 THEN 1 ELSE 0 END) as UNSIGNED) as weeks_on_no_1'),
                DB::raw('GROUP_CONCAT(position) as chart_runs'),
                DB::raw('COUNT(periods_on_chart) as total_periods_on_chart'),
                DB::raw('CAST(SUM(21 - position) as UNSIGNED) as score')
            )
            ->where('user_id', $user->id)
            ->whereYear('created_at', $year)
            ->groupBy('track_spotify_id')
            ->orderByDesc('score')
            ->limit(50);

        return Chart::where('user_id', $user->id)
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
    }
}
