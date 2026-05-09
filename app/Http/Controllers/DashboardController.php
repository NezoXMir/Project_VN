<?php

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use App\Services\StatsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly StatsService $stats,
        private readonly RecommendationService $recommendations,
    ) {
    }

    public function index(): View
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        return view('dashboard', [
            'stats' => $this->stats->dashboard($userId),
            'recommendations' => $this->recommendations->generate($userId),
            'recentAchievements' => $user->achievements()->take(3)->get(),
            'unreadNotifications' => $user->unreadNotifications()->take(10)->get(),
        ]);
    }
}
