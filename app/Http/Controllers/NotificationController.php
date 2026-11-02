<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $r, AppNotification $notification)
    {
        abort_unless($notification->user_id === $r->user()->id, 403);
        $notification->update(['is_read' => true]);

        if ($notification->link) {
            return redirect($notification->link);
        }

        return back()->with('success', 'Đã đánh dấu đã đọc thông báo.');
    }

    public function markAllAsRead(Request $r)
    {
        NotificationService::markAllAsRead($r->user());

        return back()->with('success', 'Đã đánh dấu đọc tất cả thông báo.');
    }
}
