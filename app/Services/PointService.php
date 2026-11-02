<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Criteria;
use App\Models\Event;
use App\Models\EventCompletion;
use App\Models\PointAdjustment;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\StudentPoint;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointService
{
    public function approveEventPoints(Event $event, User $actor, ?array $ids = null): array
    {
        abort_unless($actor->is_active && $event->hasStaffRole($actor, ['approver', 'manager']), 403);
        if (! $event->semester_id || ! $event->criteria_id) {
            $this->fail('Sự kiện chưa gán học kỳ và tiêu chí.');
        }
        if ($ids !== null && count($ids) > 200) {
            $this->fail('Tối đa 200 hồ sơ mỗi lần duyệt.');
        }
        $query = Registration::where('event_id', $event->id)->where('status', 'confirmed')->whereHas('completion')->orderBy('user_id');
        if ($ids !== null) {
            $query->whereIn('id', $ids);
        } else {
            $query->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('student_points')
                    ->whereColumn('student_points.student_id', 'registrations.user_id')
                    ->whereColumn('student_points.event_id', 'registrations.event_id');
            });
        }
        $candidates = $query->limit(200)->get(['id', 'user_id']);
        $result = ['eligible' => $candidates->count(), 'approved' => 0, 'capped' => 0, 'already_recorded' => 0];
        foreach ($candidates as $candidate) {
            $out = DB::transaction(function () use ($event, $candidate, $actor) {
                $e = Event::sharedLock()->findOrFail($event->id);
                if ($e->status !== 'published' || now()->lt($e->ends_at)) {
                    $this->fail('Sự kiện chưa kết thúc hoặc đã hủy.');
                }
                // All point writers lock the same student before reading the cap, even across different events.
                $u = User::lockForUpdate()->findOrFail($candidate->user_id);
                $r = Registration::lockForUpdate()->findOrFail($candidate->id);
                $c = EventCompletion::where('registration_id', $r->id)->first();
                if (! $u->is_active || $r->status !== 'confirmed' || ! $c) {
                    $this->fail('Hồ sơ không còn đủ điều kiện.');
                }
                $rule = $c->rule_snapshot;
                if (($rule['criteria_id'] ?? null) != $e->criteria_id || ($rule['semester_id'] ?? null) != $e->semester_id) {
                    $this->fail('Quy tắc đã thay đổi so với bằng chứng tham dự. Cần đối soát.');
                }
                if (StudentPoint::where('event_id', $e->id)->where('student_id', $u->id)->lockForUpdate()->first()) {
                    return 'already_recorded';
                } // Never regrant revoked points implicitly.
                $rows = StudentPoint::where('student_id', $u->id)->where('criteria_id', $e->criteria_id)->where('semester_id', $e->semester_id)->where('status', 'approved')->lockForUpdate()->get();
                $max = (int) $rule['criteria_max'];
                $proposed = max(0, (int) $rule['proposed_points']);
                $awarded = min($proposed, max(0, $max - $rows->sum('points')));
                $p = StudentPoint::create(['student_id' => $u->id, 'event_id' => $e->id, 'criteria_id' => $e->criteria_id, 'semester_id' => $e->semester_id, 'points' => $awarded, 'status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now(), 'notes' => $awarded < $proposed ? 'Giới hạn trần tiêu chí; xem snapshot quy tắc.' : null, 'rule_snapshot' => $rule]);
                AuditLog::create(['actor_id' => $actor->id, 'action' => 'points.approved', 'entity_type' => 'student_point', 'entity_id' => $p->id, 'payload' => ['points' => $awarded, 'rule' => $rule, 'completion_id' => $c->id]]);

                try {
                    NotificationService::send(
                        $u,
                        "Điểm rèn luyện: {$e->title}",
                        "Bạn vừa được ghi nhận +{$awarded} điểm rèn luyện".($awarded < $proposed ? " (Chạm trần tiêu chí)" : "")." cho sự kiện \"{$e->title}\".",
                        '/my-points',
                        'point',
                        'star'
                    );
                } catch (\Throwable) {}

                return $awarded < $proposed ? 'capped' : 'approved';
            }, 3);
            if ($out === 'already_recorded') {
                $result['already_recorded']++;
            } else {
                $result['approved']++;
                if ($out === 'capped') {
                    $result['capped']++;
                }
            }
        }

        return $result;
    }

    public function adjustPoints(StudentPoint $point, int $newPoints, string $reason, User $actor): StudentPoint
    {
        abort_unless($actor->is_active && $point->event->hasStaffRole($actor, ['approver', 'manager']), 403);
        if (! trim($reason) || $newPoints < 0) {
            $this->fail('Điểm không âm và phải có lý do điều chỉnh.');
        }

        return DB::transaction(function () use ($point, $newPoints, $reason, $actor) {
            $e = Event::sharedLock()->findOrFail($point->event_id);
            $u = User::lockForUpdate()->findOrFail($point->student_id);
            $p = StudentPoint::lockForUpdate()->findOrFail($point->id);

            // Giảm/thu hồi điểm luôn được phép (sửa sai, xử lý vi phạm).
            // Tăng/khôi phục điểm yêu cầu sinh viên đang hoạt động, sự kiện published và không được tự duyệt cho bản thân.
            if ($newPoints > $p->points) {
                if (! $u->is_active) {
                    $this->fail('Sinh viên đang bị khóa tài khoản, không thể tăng điểm.');
                }
                if ($e->status !== 'published') {
                    $this->fail('Sự kiện không ở trạng thái hợp lệ để tăng điểm.');
                }
                if ($actor->id === $u->id) {
                    $this->fail('Người có quyền không thể tự điều chỉnh tăng điểm cho chính mình.');
                }
            }

            if ($newPoints > 0 && ! EventCompletion::where('event_id', $p->event_id)->where('user_id', $p->student_id)->exists()) {
                $this->fail('Chưa có bằng chứng hoàn thành theo quy trình mới.');
            }
            $max = (int) ($p->rule_snapshot['criteria_max'] ?? $p->criteria->max_points);
            $others = StudentPoint::where('student_id', $p->student_id)->where('criteria_id', $p->criteria_id)->where('semester_id', $p->semester_id)->where('id', '!=', $p->id)->where('status', 'approved')->lockForUpdate()->get()->sum('points');
            if ($newPoints + $others > $max) {
                $this->fail('Tổng điểm sau điều chỉnh vượt trần tiêu chí trong học kỳ.');
            }
            PointAdjustment::create(['student_point_id' => $p->id, 'adjusted_by' => $actor->id, 'old_points' => $p->points, 'new_points' => $newPoints, 'reason' => trim($reason)]);
            $old = $p->points;
            $p->update(['points' => $newPoints, 'status' => $newPoints === 0 ? 'revoked' : 'approved', 'notes' => trim($reason)]);
            AuditLog::create(['actor_id' => $actor->id, 'action' => 'points.adjusted', 'entity_type' => 'student_point', 'entity_id' => $p->id, 'payload' => ['old_points' => $old, 'new_points' => $newPoints, 'reason' => trim($reason)]]);

            try {
                NotificationService::send(
                    $u,
                    "Điều chỉnh điểm: {$e->title}",
                    "Điểm rèn luyện của bạn tại \"{$e->title}\" đã được điều chỉnh thành {$newPoints} điểm. Lý do: {$reason}",
                    '/my-points',
                    'point',
                    'alert'
                );
            } catch (\Throwable) {}

            return $p->fresh();
        }, 3);
    }

    public function getStudentSummary(User $student, ?int $semesterId = null): array
    {
        $semesters = Semester::when($semesterId, fn ($q) => $q->where('id', $semesterId))
            ->orderBy('id', 'desc')
            ->get();

        $allCriterias = Criteria::orderBy('code')->get();
        $studentRegistrations = Registration::where('user_id', $student->id)->pluck('event_id')->all();

        $results = [];

        foreach ($semesters as $sem) {
            $records = StudentPoint::where('student_id', $student->id)
                ->where('semester_id', $sem->id)
                ->with(['event.category', 'criteria', 'adjustments.user'])
                ->get();

            // Tính các ca điểm danh đã hoàn thành nhưng đang chờ BTC duyệt điểm
            $pendingCompletions = EventCompletion::where('user_id', $student->id)
                ->whereHas('event', fn ($q) => $q->where('semester_id', $sem->id)->where('status', 'published'))
                ->whereDoesntHave('event.studentPoints', fn ($q) => $q->where('student_id', $student->id))
                ->with(['event.criteria', 'event.category'])
                ->get();

            $byCriteria = [];
            $totalApproved = 0;
            $totalPending = 0;
            $totalRevoked = 0;

            // Khởi tạo tất cả tiêu chí của trường
            foreach ($allCriterias as $crit) {
                $byCriteria[$crit->id] = [
                    'criteria' => $crit,
                    'total' => 0,
                    'approved_points' => 0,
                    'pending_points' => 0,
                    'revoked_points' => 0,
                    'max_points' => (int) $crit->max_points,
                    'items' => [],
                    'pending_items' => [],
                ];
            }

            // Ghi nhận điểm chính thức & điều chỉnh
            foreach ($records as $r) {
                $critId = $r->criteria_id;
                if (! isset($byCriteria[$critId])) {
                    $byCriteria[$critId] = [
                        'criteria' => $r->criteria,
                        'total' => 0,
                        'approved_points' => 0,
                        'pending_points' => 0,
                        'revoked_points' => 0,
                        'max_points' => (int) ($r->criteria->max_points ?? 20),
                        'items' => [],
                        'pending_items' => [],
                    ];
                }

                // Tính giải trình trần điểm nếu có
                $rule = $r->rule_snapshot ?? [];
                $proposed = (int) ($rule['proposed_points'] ?? ($r->event->proposed_points ?? $r->points));
                $cappingNote = null;
                if ($r->status === 'approved' && $r->points < $proposed) {
                    $max = $byCriteria[$critId]['max_points'];
                    $cappingNote = "Sự kiện đề xuất {$proposed} điểm; bạn còn {$r->points} điểm trước khi đạt trần ({$max}đ) nên được ghi nhận {$r->points} điểm.";
                }

                $r->capping_note = $cappingNote;

                if ($r->status === 'approved') {
                    $byCriteria[$critId]['approved_points'] += $r->points;
                    $byCriteria[$critId]['total'] += $r->points;
                    $totalApproved += $r->points;
                } elseif ($r->status === 'revoked') {
                    $byCriteria[$critId]['revoked_points'] += $r->points;
                    $totalRevoked += $r->points;
                }

                $byCriteria[$critId]['items'][] = $r;
            }

            // Ghi nhận điểm chờ duyệt
            foreach ($pendingCompletions as $pc) {
                $critId = $pc->event->criteria_id;
                if ($critId && isset($byCriteria[$critId])) {
                    $proposed = (int) ($pc->event->proposed_points ?? 0);
                    $byCriteria[$critId]['pending_points'] += $proposed;
                    $byCriteria[$critId]['pending_items'][] = $pc;
                    $totalPending += $proposed;
                }
            }

            // Tính phần trăm & thiếu điểm cho từng tiêu chí
            $criteriaGaps = [];
            foreach ($byCriteria as $critId => &$cData) {
                $cData['gap'] = max(0, $cData['max_points'] - $cData['approved_points']);
                $cData['percentage'] = min(100, round(($cData['approved_points'] / max(1, $cData['max_points'])) * 100));
                if ($cData['gap'] > 0) {
                    $criteriaGaps[$critId] = $cData['gap'];
                }
            }
            unset($cData);

            // Gợi ý sự kiện thông minh dựa trên tiêu chí còn thiếu
            arsort($criteriaGaps);
            $missingCriteriaIds = array_keys($criteriaGaps);

            $recommendations = [];
            if (! empty($missingCriteriaIds)) {
                $recommendEvents = Event::where('semester_id', $sem->id)
                    ->where('status', 'published')
                    ->where('ends_at', '>=', now())
                    ->where('closes_at', '>', now())
                    ->whereIn('criteria_id', $missingCriteriaIds)
                    ->whereNotIn('id', $studentRegistrations)
                    ->with(['criteria', 'category'])
                    ->orderBy('starts_at', 'asc')
                    ->limit(4)
                    ->get();

                foreach ($recommendEvents as $rev) {
                    $critName = $rev->criteria?->name ?? 'chuyên đề';
                    $gap = $criteriaGaps[$rev->criteria_id] ?? 0;
                    $rev->recommend_reason = "Bạn còn thiếu {$gap} điểm ở tiêu chí \"{$critName}\". Tham gia để tích lũy +{$rev->proposed_points} ĐRL.";
                    $recommendations[] = $rev;
                }
            }

            $results[] = [
                'semester' => $sem,
                'total_points' => $totalApproved,
                'total_approved' => $totalApproved,
                'total_pending' => $totalPending,
                'total_revoked' => $totalRevoked,
                'max_possible' => collect($byCriteria)->sum('max_points'),
                'by_criteria' => $byCriteria,
                'recommendations' => $recommendations,
            ];
        }

        return $results;
    }

    private function fail(string $m): never
    {
        throw ValidationException::withMessages(['points' => $m]);
    }
}
