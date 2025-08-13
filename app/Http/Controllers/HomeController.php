<?php namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

use App\Services\Spotify;

use App\Models\User;
use App\Models\Chart;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Spotify $spotify)
    {
        $users = User::has('latest_chart')->with(['latest_chart', 'latest_chart_top_tracks'])->paginate(20);
        return view('index', compact('users'));
    }
}
