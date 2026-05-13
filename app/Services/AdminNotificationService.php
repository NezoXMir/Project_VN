<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminNotificationService
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = DB::table('notifications')
            ->leftJoin('users', 'users.id', '=', 'notifications.notifiable_id')
            ->select(
                'notifications.id',
                'notifications.type',
                'notifications.data',
                'notifications.read_at',
                'notifications.created_at',
                'notifications.notifiable_id',
                'users.name as user_name',
                'users.email as user_email',
            )
            ->where('notifications.notifiable_type', 'App\\Models\\User')
            ->orderByDesc('notifications.created_at');

        if (! empty($filters['user_id'])) {
            $query->where('notifications.notifiable_id', (int) $filters['user_id']);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'read') {
                $query->whereNotNull('notifications.read_at');
            } elseif ($filters['status'] === 'unread') {
                $query->whereNull('notifications.read_at');
            }
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function stats(): array
    {
        $total     = DB::table('notifications')->count();
        $unread    = DB::table('notifications')->whereNull('read_at')->count();
        $sent30d   = DB::table('notifications')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $readRate  = $total > 0 ? (int) round(($total - $unread) / $total * 100) : 0;

        return compact('total', 'unread', 'sent30d', 'readRate');
    }

    public function delete(string $id): void
    {
        DB::table('notifications')->where('id', $id)->delete();
    }

    /** Удалить все прочитанные уведомления старше $days дней. */
    public function purgeRead(int $days = 30): int
    {
        return DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays($days))
            ->delete();
    }
}
