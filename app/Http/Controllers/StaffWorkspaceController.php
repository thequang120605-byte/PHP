<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventCompletion;
use App\Models\EventStaff;
use App\Models\StudentPoint;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class StaffWorkspaceController extends Controller
{
    /**
     * Không gian làm việc BTC: "Sự kiện tôi phụ trách"
     */
    public function index(Request $r)
    {
        $user = $r->user();
        $roleFilter = $r->input('role'); // all, scanner, approver, manager

        $query = Event::with(['category', 'semester', 'criteria', 'staffs.user']);

        if ($user->role !== 'admin') {
            $query->whereHas('staffs', function ($q) use ($user, $roleFilter) {
                $q->where('user_id', $user->id);
                if ($roleFilter && in_array($roleFilter, ['scanner', 'approver', 'manager'])) {
                    $q->where('role', $roleFilter);
                }
            });
        } elseif ($roleFilter && in_array($roleFilter, ['scanner', 'approver', 'manager'])) {
            $query->whereHas('staffs', fn ($q) => $q->where('role', $roleFilter));
        }

        $events = $query->orderBy('starts_at', 'desc')->paginate(15);

        // Bổ sung dữ liệu vận hành & checklist cho từng sự kiện
        foreach ($events as $event) {
            $event->my_roles = $user->role === 'admin'
                ? ['manager', 'approver', 'scanner']
                : $event->staffs->where('user_id', $user->id)->pluck('role')->all();

            $event->confirmed_count = $event->confirmed()->count();
            $event->completion_count = EventCompletion::where('event_id', $event->id)->count();
            $event->approved_points_count = StudentPoint::where('event_id', $event->id)->where('status', 'approved')->count();
            $event->pending_points_count = max(0, $event->completion_count - $event->approved_points_count);
            $event->pending_disputes_count = $event->disputes()->where('status', 'pending')->count();

            // Checklist vận hành 5 bước
            $event->checklist = [
                'has_staff' => $event->staffs->isNotEmpty(),
                'has_rule' => $event->semester_id && $event->criteria_id,
                'has_attendance' => $event->completion_count > 0,
                'has_approval' => $event->approved_points_count > 0,
                'is_finalized' => (bool) $event->is_finalized,
            ];
        }

        return view('staff.workspace', compact('events', 'roleFilter'));
    }

    /**
     * Quy trình chốt kết quả sự kiện (Khóa sổ)
     */
    public function finalize(Request $r, Event $event)
    {
        $user = $r->user();
        abort_unless($event->hasStaffRole($user, ['manager']), 403, 'Chỉ Trưởng ban tổ chức (Manager) hoặc Admin mới có quyền chốt kết quả sự kiện.');

        if ($event->is_finalized) {
            return back()->with('success', 'Sự kiện này đã được chốt kết quả trước đó.');
        }

        if (now()->lt($event->ends_at)) {
            return back()->withErrors(['event' => 'Chỉ có thể chốt kết quả sau khi sự kiện đã kết thúc thời gian tổ chức.']);
        }

        $pendingDisputes = $event->disputes()->where('status', 'pending')->count();
        if ($pendingDisputes > 0 && ! $r->boolean('force_finalize')) {
            return back()->with('pending_disputes_warning', [
                'count' => $pendingDisputes,
                'event_id' => $event->id,
            ]);
        }

        $notes = $r->input('notes', 'Hoàn tất rà soát đối soát tham dự và điểm danh.');

        $event->update([
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => $user->id,
            'finalized_notes' => $notes,
        ]);

        AuditLog::create([
            'actor_id' => $user->id,
            'action' => 'event.finalized',
            'entity_type' => 'event',
            'entity_id' => $event->id,
            'payload' => [
                'finalized_at' => now()->toIso8601String(),
                'notes' => $notes,
            ],
        ]);

        // Gửi thông báo đến sinh viên tham dự
        $attendees = $event->confirmed()->with('user')->get()->pluck('user');
        NotificationService::sendToUsers(
            $attendees,
            "Sự kiện \"{$event->title}\" đã chốt kết quả ĐRL",
            "Ban Tổ Chức đã hoàn tất rà soát và chốt sổ điểm rèn luyện chính thức. Nhấp để xem bảng điểm cá nhân.",
            '/my-points',
            'finalization',
            'check'
        );

        return back()->with('success', "Đã chốt kết quả sự kiện \"{$event->title}\" thành công.");
    }
}
