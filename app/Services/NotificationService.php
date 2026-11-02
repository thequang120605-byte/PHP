<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    public static function send(User|int $user, string $title, string $message, ?string $link = null, string $type = 'info', string $icon = 'bell'): AppNotification
    {
        $userId = $user instanceof User ? $user->id : $user;

        return AppNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'type' => $type,
            'icon' => $icon,
            'is_read' => false,
        ]);
    }

    public static function sendToUsers(iterable $users, string $title, string $message, ?string $link = null, string $type = 'info', string $icon = 'bell'): void
    {
        foreach ($users as $user) {
            self::send($user, $title, $message, $link, $type, $icon);
        }
    }

    public static function markAsRead(int $notificationId, User $user): bool
    {
        $notif = AppNotification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if ($notif) {
            $notif->update(['is_read' => true]);
            return true;
        }

        return false;
    }

    public static function markAllAsRead(User $user): int
    {
        return AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public static function getRecent(User $user, int $limit = 8)
    {
        return AppNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getUnreadCount(User $user): int
    {
        return AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }
}
