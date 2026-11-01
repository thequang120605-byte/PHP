<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Criteria;
use App\Models\Event;
use App\Models\EventCompletion;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\StudentPoint;
use App\Services\PointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointController extends Controller
{
    public function __construct(protected PointService $pointService) {}

    /**
     * Sinh viên xem bảng điểm rèn luyện cá nhân
     */
    public function myPoints(Request $r)
    {
        $user = $r->user();
        $semesterId = $r->input('semester_id');
        $semesters = Semester::orderBy('id', 'desc')->get();
        $transcript = $this->pointService->getStudentSummary($user, $semesterId ? (int) $semesterId : null);

        return view('points.mine', compact('transcript', 'semesters', 'semesterId'));
    }

    /**
     * Màn hình duyệt điểm rèn luyện sự kiện cho Admin / Approver
     */
    public function showReviewPage(Event $event, Request $r)
    {
        abort_unless($event->hasStaffRole($r->user(), ['approver', 'manager']), 403, 'Bạn không có quyền duyệt điểm cho sự kiện này.');

        $eligibleRegistrations = Registration::where('event_id', $event->id)
            ->where('status', 'confirmed')
            ->whereHas('completion')
            ->with(['user', 'completion'])
            ->paginate(100);

        $recordedPoints = StudentPoint::where('event_id', $event->id)
            ->whereIn('student_id', $eligibleRegistrations->pluck('user_id'))
            ->get()
            ->keyBy('student_id');

        return view('points.review', compact('event', 'eligibleRegistrations', 'recordedPoints'));
    }

    /**
     * Xử lý duyệt điểm rèn luyện
     */
    public function approvePoints(Request $r, Event $event)
    {
        abort_unless($event->hasStaffRole($r->user(), ['approver', 'manager']), 403);

        $r->validate(['registration_ids' => 'nullable|array|max:200', 'registration_ids.*' => 'integer|distinct']);
        $selectedIds = $r->input('registration_ids');

        $res = $this->pointService->approveEventPoints($event, $r->user(), $selectedIds);

        return back()->with('success', "Đã ghi nhận điểm rèn luyện: {$res['approved']} sinh viên được cấp điểm ({$res['capped']} đạt trần tiêu chí, {$res['already_recorded']} đã cấp trước đó).");
    }

    /**
     * Điều chỉnh / thu hồi điểm rèn luyện
     */
    public function adjustPoint(Request $r, StudentPoint $point)
    {
        $event = $point->event;
        abort_unless($event->hasStaffRole($r->user(), ['approver', 'manager']), 403);

        $r->validate([
            'new_points' => 'required|integer|min:0|max:100',
            'reason' => 'required|string|max:500',
        ]);

        $this->pointService->adjustPoints($point, (int) $r->input('new_points'), $r->input('reason'), $r->user());

        return back()->with('success', "Đã cập nhật điểm cho sinh viên {$point->student->name}. Lý do: {$r->input('reason')}");
    }

    /**
     * Quản lý Học kỳ (Admin)
     */
    public function semesters()
    {
        $semesters = Semester::withCount('events')->orderBy('id', 'desc')->get();

        return view('admin.semesters', compact('semesters'));
    }

    public function saveSemester(Request $r, ?Semester $semester = null)
    {
        $d = $r->validate([
            'code' => 'required|string|max:30|unique:semesters,code,'.($semester?->id ?? 'NULL').',id',
            'name' => 'required|string|max:100',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'nullable|boolean',
        ]);

        $d['is_active'] = $r->has('is_active');

        if ($semester) {
            $semester->update($d);
        } else {
            Semester::create($d);
        }

        return back()->with('success', 'Đã lưu thông tin học kỳ.');
    }

    /**
     * Quản lý Tiêu chí rèn luyện (Admin)
     */
    public function criterias()
    {
        $criterias = Criteria::withCount('events')->orderBy('code')->get();

        return view('admin.criterias', compact('criterias'));
    }

    public function saveCriteria(Request $r, ?Criteria $criteria = null)
    {
        $d = $r->validate([
            'code' => 'required|string|max:30|unique:criterias,code,'.($criteria?->id ?? 'NULL').',id',
            'name' => 'required|string|max:180',
            'max_points' => 'required|integer|min:1|max:100',
            'rule_version' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($criteria, $d) {
            if ($criteria) {
                $criteria = Criteria::lockForUpdate()->findOrFail($criteria->id);
                $used = $criteria->points()->exists() || AttendanceSession::whereIn('event_id', $criteria->events()->select('id'))->exists() || EventCompletion::whereIn('event_id', $criteria->events()->select('id'))->exists();
                if ($used && ($criteria->code !== $d['code'] || $criteria->max_points != (int) $d['max_points'] || $criteria->rule_version !== $d['rule_version'])) {
                    throw ValidationException::withMessages(['criteria' => 'Tiêu chí đã có lịch sử. Tạo tiêu chí phiên bản mới, không sửa mã/trần/phiên bản cũ.']);
                }
                $criteria->update($d);
            } else {
                Criteria::create($d);
            }

        }, 3);

        return back()->with('success', 'Đã lưu tiêu chí rèn luyện.');
    }
}
