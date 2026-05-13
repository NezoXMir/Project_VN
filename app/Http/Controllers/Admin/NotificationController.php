<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private readonly AdminNotificationService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters       = $request->only(['user_id', 'status']);
        $notifications = $this->service->paginate($filters);
        $stats         = $this->service->stats();

        return view('admin.notifications.index', compact('notifications', 'filters', 'stats'));
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->service->delete($id);

        return back()->with('success', 'Уведомление удалено.');
    }

    public function purge(Request $request): RedirectResponse
    {
        $days    = max(1, (int) $request->input('days', 30));
        $deleted = $this->service->purgeRead($days);

        return back()->with('success', "Удалено {$deleted} прочитанных уведомлений старше {$days} дней.");
    }
}
