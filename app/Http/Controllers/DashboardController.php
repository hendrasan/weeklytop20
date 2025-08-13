<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Chart;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $latest_period = Chart::where('user_id', $user->id)->max('period');
        $chart = Chart::where('user_id', $user->id)->where('period', $latest_period)->get();
        return view('dashboard', compact('user', 'chart'));
    }
}
