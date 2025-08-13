<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Chart extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithChartRuns($query,$user_id)
    {
        return $query->addSelect(['chart_runs' =>
            DB::table('charts', 'c')
            ->selectRaw('JSON_ARRAYAGG(position)')
            ->whereColumn('track_spotify_id', 'charts.track_spotify_id')
            ->where('user_id', $user_id)
        ]);
    }
}
