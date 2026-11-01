<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventStaff;
use App\Models\User;
use Illuminate\Http\Request;

class EventStaffController extends Controller
{
    public function index(Event $event)
    {
        $staffs = EventStaff::where('event_id', $event->id)
            ->with(['user', 'assigner'])
            ->get();

        $availableUsers = User::where('is_active', true)->orderBy('name')->get();

        return view('admin.event-staffs', compact('event', 'staffs', 'availableUsers'));
    }

    public function assign(Request $r, Event $event)
    {
        $r->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:scanner,approver,manager',
        ]);

        $userId = $r->input('user_id');
        $role = $r->input('role');

        $staff = EventStaff::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $userId, 'role' => $role],
            ['assigned_by' => $r->user()->id]
        );

        AuditLog::create([
            'actor_id' => $r->user()->id,
            'action' => 'staff.assigned',
            'entity_type' => 'event',
            'entity_id' => $event->id,
            'payload' => [
                'target_user_id' => $userId,
                'role' => $role,
            ],
        ]);

        return back()->with('success', 'Đã phân công nhân sự thành công.');
    }

    public function remove(EventStaff $staff, Request $r)
    {
        $eventId = $staff->event_id;
        $staffUser = $staff->user;

        $staff->delete();

        AuditLog::create([
            'actor_id' => $r->user()->id,
            'action' => 'staff.removed',
            'entity_type' => 'event',
            'entity_id' => $eventId,
            'payload' => [
                'user' => $staffUser->name,
                'role' => $staff->role,
            ],
        ]);

        return back()->with('success', 'Đã gỡ quyền nhân sự khỏi sự kiện.');
    }
}
