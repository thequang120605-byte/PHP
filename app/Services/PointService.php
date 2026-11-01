<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventCompletion;
use App\Models\PointAdjustment;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\StudentPoint;
use App\Models\User;
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
            Event::sharedLock()->findOrFail($point->event_id);
            User::lockForUpdate()->findOrFail($point->student_id);
            $p = StudentPoint::lockForUpdate()->findOrFail($point->id);
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

            return $p->fresh();
        }, 3);
    }

    public function getStudentSummary(User $student, ?int $semesterId = null): array
    {
        $semesters = Semester::when($semesterId, fn ($q) => $q->where('id', $semesterId))
            ->orderBy('id', 'desc')
            ->get();

        $results = [];

        foreach ($semesters as $sem) {
            $records = StudentPoint::where('student_id', $student->id)
                ->where('semester_id', $sem->id)
                ->with(['event', 'criteria', 'adjustments.user'])
                ->get();

            $byCriteria = [];
            $totalPoints = 0;

            foreach ($records as $r) {
                $critId = $r->criteria_id;
                if (! isset($byCriteria[$critId])) {
                    $byCriteria[$critId] = [
                        'criteria' => $r->criteria,
                        'total' => 0,
                        'items' => [],
                    ];
                }

                if ($r->status === 'approved') {
                    $byCriteria[$critId]['total'] += $r->points;
                    $totalPoints += $r->points;
                }

                $byCriteria[$critId]['items'][] = $r;
            }

            $results[] = [
                'semester' => $sem,
                'total_points' => $totalPoints,
                'by_criteria' => $byCriteria,
            ];
        }

        return $results;
    }

    private function fail(string $m): never
    {
        throw ValidationException::withMessages(['points' => $m]);
    }
}
