<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Services\AchievementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function __construct(private readonly AchievementService $achievements)
    {
    }

    public function index(): View
    {
        $userId = (int) Auth::id();

        $unlockedAt = DB::table('user_achievements')
            ->where('user_id', $userId)
            ->pluck('unlocked_at', 'achievement_id');

        $progress = $this->achievements->progressFor($userId);

        $achievements = Achievement::query()
            ->orderBy('sort_order')
            ->get()
            ->map(function (Achievement $a) use ($unlockedAt, $progress) {
                $a->unlocked_at = $unlockedAt[$a->id] ?? null;
                $a->progress = $progress[$a->code] ?? null;

                return $a;
            });

        $unlockedCount = $achievements->whereNotNull('unlocked_at')->count();
        $totalCount = $achievements->count();

        return view('achievements.index', [
            'achievements' => $achievements,
            'unlockedCount' => $unlockedCount,
            'totalCount' => $totalCount,
        ]);
    }
}
