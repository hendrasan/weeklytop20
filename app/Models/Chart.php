<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Chart extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithChartRuns(Builder $query, int $userId): Builder
    {
        $table = $query->getModel()->getTable(); // usually 'charts'

        $sub = DB::query()
            ->fromSub(function ($q) use ($userId, $table) {
                $q->from('charts as c')
                    ->select(['c.period', 'c.position', 'c.created_at'])
                    ->where('c.user_id', $userId)
                    ->whereColumn('c.track_spotify_id', $table . '.track_spotify_id')
                    ->orderBy('c.period');
            }, 'c2')
            ->selectRaw("
            JSON_ARRAYAGG(
                JSON_OBJECT(
                    'period', c2.period,
                    'chart_date',   DATE(c2.created_at),
                    'position', c2.position
                )
            )
        ");

        return $query->addSelect(['chart_runs' => $sub]);
    }
}
