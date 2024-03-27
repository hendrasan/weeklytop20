<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'spotify_id',
        'spotify_access_token',
        'spotify_refresh_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the charts for the user.
     */
    public function charts()
    {
        return $this->hasMany(Chart::class);
    }

    public function latest_chart()
    {
        // get charts table with period = max period for each user
        return $this->hasOne(Chart::class)
            ->whereRaw('charts.position = 1')
            ->join(DB::raw('(SELECT user_id, MAX(period) AS max_period FROM charts GROUP BY user_id) as max_periods'), function ($join) {
                $join->on('charts.user_id', '=', 'max_periods.user_id')
                    ->on('charts.period', '=', 'max_periods.max_period');
            });
    }

    public function latest_chart_top_tracks()
    {
        return $this->hasMany(Chart::class)
            ->select('charts.user_id', 'charts.track_name', 'charts.track_artist', 'charts.position', 'charts.last_position', 'charts.period')
            ->whereRaw('charts.position <= 3')
            ->join(DB::raw('(SELECT user_id, MAX(period) AS max_period FROM charts GROUP BY user_id) as max_periods'), function ($join) {
                $join->on('charts.user_id', '=', 'max_periods.user_id')
                ->on('charts.period', '=', 'max_periods.max_period');
            });
    }

}
