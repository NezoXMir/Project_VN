<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminStatsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminStatsService $stats)
    {
    }

    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => $this->stats->dashboard(),
        ]);
    }
}
