<?php

namespace App\Http\Controllers;

use App\Services\StatsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly StatsService $stats)
    {
    }

    public function index(): View
    {
        return view('dashboard', [
            'stats' => $this->stats->dashboard((int) Auth::id()),
        ]);
    }
}
