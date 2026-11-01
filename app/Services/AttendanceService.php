<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Criteria;
use App\Models\Event;
use App\Models\EventCompletion;
use App\Models\PaperAttendance;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public const TTL_SECONDS = 600;

    public const SLOT_SECONDS = 15;

    public function open(Event $event, User $actor, ?string $reason = null): AttendanceSession
    {
        $this->authorizeOperator($event, $actor);

        return DB::transaction(function () use ($event, $actor, $reason) {
            $e = Event::lockForUpdate()->findOrFail($event->id);
            if ($e->status !== 'published' || now()->lt($e->ends_at)) {
                $this->fail('Chỉ mở điểm danh kết thúc sau giờ kết thúc của sự kiện đã công bố.');
            }
            $previous = AttendanceSession::where('event_id', $e->id)->latest('opened_at')->orderByRaw('revoked_at IS NULL DESC')->latest('id')->first();
            if ($previous?->active()) {
                return $previous;
            } // Double-click never extends the deadline.
            if ($previous && ! trim((string) $reason)) {
                $this->fail('Mở lại đợt mới phải ghi rõ lý do. Đợt cũ không được gia hạn.');
            }
            if ($e->proposed_points > 0 && (! $e->criteria_id || ! $e->semester_id)) {
                $this->fail('Cần gán học kỳ và tiêu chí trước khi mở đợt có điểm đề xuất.');
            }
            $secret = bin2hex(random_bytes(32));
            $stamp = now()->startOfSecond();
            $s = new AttendanceSession(['event_id' => $e->id, 'opened_by' => $actor->id, 'secret_ciphertext' => Crypt::encryptString($secret), 'token_hash' => hash('sha256', $secret), 'opened_at' => $stamp, 'expires_at' => $stamp->copy()->addSeconds(self::TTL_SECONDS), 'point_rule_snapshot' => $this->rule($e), 'reason' => $reason]);
            $s->id = (string) Str::uuid();
            $s->save();
            $this->audit($actor, 'attendance.opened', 'event', $e->id, ['session_id' => $s->id, 'expires_at' => $s->expires_at->toIso8601String(), 'reason' => $reason]);

            return $s;
        }, 3);
    }

    public function close(Event $event, User $actor): void
    {
        $this->authorizeOperator($event, $actor);
        DB::transaction(function () use ($event, $actor) {
            Event::lockForUpdate()->findOrFail($event->id);
            AttendanceSession::where('event_id', $event->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $this->audit($actor, 'attendance.closed', 'event', $event->id, []);
        }, 3);
    }

    public function current(Event $event): ?AttendanceSession
    {
        return AttendanceSession::where('event_id', $event->id)->latest('opened_at')->orderByRaw('revoked_at IS NULL DESC')->latest('id')->first();
    }

    public function payload(AttendanceSession $s): string
    {
        $slot = intdiv(now()->timestamp, self::SLOT_SECONDS);
        $body = $s->id.'.'.$slot;

        return $body.'.'.hash_hmac('sha256', $body.'|'.$s->event_id, Crypt::decryptString($s->secret_ciphertext));
    }

    public function complete(Event $event, User $student, string $payload): array
    {
        if (! preg_match('/^([0-9a-f-]{36})\.(\d{1,12})\.([0-9a-f]{64})$/D', $payload, $m)) {
            $this->fail('Mã điểm danh không hợp lệ.');
        }

        return DB::transaction(function () use ($event, $student, $m) {
            // Shared locks allow different students through concurrently and synchronize revocation/cancellation.
            $e = Event::sharedLock()->findOrFail($event->id);
            $s = AttendanceSession::where('id', $m[1])->where('event_id', $e->id)->sharedLock()->first();
            $u = User::sharedLock()->findOrFail($student->id);
            if (! $s || ! $s->active() || $e->status !== 'published') {
                $this->fail('Đợt điểm danh đã đóng hoặc mã hết hạn sau 10 phút.');
            }
            if (! $u->is_active || $u->role !== 'student') {
                $this->fail('Tài khoản không đủ điều kiện điểm danh.');
            }
            $r = Registration::where('event_id', $e->id)->where('user_id', $u->id)->lockForUpdate()->first();
            if (! $r || $r->status !== 'confirmed') {
                $this->fail('Bạn chưa có đăng ký chính thức hoặc đã hủy/đang chờ.');
            }
            // Recheck AFTER waiting for the per-student lock. Exactly expires_at is rejected.
            if (! $s->active()) {
                $this->fail('Mã điểm danh đã hết hạn.');
            }
            $slot = (int) $m[2];
            $nowSlot = intdiv(now()->timestamp, self::SLOT_SECONDS);
            if ($slot > $nowSlot || $slot < $nowSlot - 1) {
                $this->fail('Ảnh mã QR đã cũ. Hãy quét mã đang hiển thị tại sự kiện.');
            }
            $body = $m[1].'.'.$m[2];
            $expected = hash_hmac('sha256', $body.'|'.$e->id, Crypt::decryptString($s->secret_ciphertext));
            if (! hash_equals($expected, $m[3])) {
                $this->fail('Chữ ký mã điểm danh không hợp lệ.');
            }
            $existing = EventCompletion::where('registration_id', $r->id)->first();
            if ($existing) {
                return ['duplicate' => true, 'completion' => $existing];
            }
            $c = EventCompletion::create(['event_id' => $e->id, 'user_id' => $u->id, 'registration_id' => $r->id, 'attendance_session_id' => $s->id, 'method' => 'end_qr', 'completed_at' => now(), 'recorded_by' => $u->id, 'rule_snapshot' => $s->point_rule_snapshot]);
            $r->update(['completed_at' => $c->completed_at, 'check_out_at' => $c->completed_at, 'attendance_status' => 'attended_complete', 'attendance_source' => 'qr']);
            $this->audit($u, 'attendance.completed', 'registration', $r->id, ['session_id' => $s->id, 'method' => 'end_qr']);

            return ['duplicate' => false, 'completion' => $c];
        }, 3);
    }

    public function importPaperAttendance(Event $event, array $entries, ?string $path, ?string $notes, User $actor): PaperAttendance
    {
        $this->authorizeOperator($event, $actor);
        if (! $path || ! trim((string) $notes)) {
            $this->fail('Điểm danh giấy cần bản scan và ghi chú xác minh.');
        }
        if (count($entries) > 500) {
            $this->fail('Tối đa 500 dòng mỗi đợt đối soát giấy.');
        }

        return DB::transaction(function () use ($event, $entries, $path, $notes, $actor) {
            $e = Event::sharedLock()->findOrFail($event->id);
            if ($e->status !== 'published' || now()->lt($e->ends_at)) {
                $this->fail('Chỉ đối soát giấy sau khi sự kiện kết thúc.');
            }
            $paper = PaperAttendance::create(['event_id' => $e->id, 'entered_by' => $actor->id, 'scan_file_path' => $path, 'notes' => $notes, 'total_records' => count($entries)]);
            // Stable ordering prevents two batches acquiring registration locks in opposite order.
            usort($entries, fn ($a, $b) => strcmp($a['student_code'] ?? '', $b['student_code'] ?? ''));
            foreach ($entries as $row) {
                $code = trim($row['student_code'] ?? '');
                $u = User::where('student_code', $code)->sharedLock()->first();
                if (! $u || ! $u->is_active || $u->role !== 'student') {
                    $this->fail('Không tìm thấy sinh viên đang hoạt động: '.$code);
                }
                $r = Registration::where('event_id', $e->id)->where('user_id', $u->id)->lockForUpdate()->first();
                if (! $r || $r->status !== 'confirmed') {
                    $this->fail('MSSV chưa có đăng ký chính thức: '.$code);
                }
                if (empty($row['has_check_out'])) {
                    $this->fail('Cần xác nhận có mặt cuối sự kiện cho MSSV '.$code);
                }
                if (EventCompletion::where('registration_id', $r->id)->exists()) {
                    continue;
                }
                $c = EventCompletion::create(['event_id' => $e->id, 'user_id' => $u->id, 'registration_id' => $r->id, 'method' => 'paper_review', 'completed_at' => now(), 'recorded_by' => $actor->id, 'paper_sheet_id' => $paper->id, 'rule_snapshot' => $this->rule($e)]);
                $r->update(['completed_at' => $c->completed_at, 'attendance_status' => 'attended_complete', 'attendance_source' => 'paper']);
                $this->audit($actor, 'attendance.paper_verified', 'registration', $r->id, ['paper_sheet_id' => $paper->id, 'recorded_at' => now()->toIso8601String(), 'reason' => $notes]);
            }

            return $paper;
        }, 3);
    }

    public function reconcile(Event $event): array
    {
        // Reporting only. Never infer completion from old arrival or missing checkout timestamps.
        return ['completed' => $event->confirmed()->whereHas('completion')->count(), 'needs_verification' => $event->confirmed()->whereDoesntHave('completion')->whereIn('attendance_status', ['needs_verification', 'attended_complete'])->count(), 'absent' => $event->confirmed()->whereDoesntHave('completion')->whereNotIn('attendance_status', ['needs_verification', 'attended_complete'])->count()];
    }

    public function rule(Event $e): array
    {
        $criteria = $e->criteria_id ? Criteria::whereKey($e->criteria_id)->sharedLock()->first() : null;

        return ['semester_id' => $e->semester_id, 'criteria_id' => $e->criteria_id, 'proposed_points' => $e->proposed_points, 'criteria_max' => $criteria?->max_points, 'rule_version' => $criteria?->rule_version, 'attendance_rule' => 'end_only'];
    }

    private function authorizeOperator(Event $e, User $u): void
    {
        abort_unless($u->is_active && $e->hasStaffRole($u, ['scanner', 'manager']), 403);
    }

    private function audit(User $u, string $action, string $type, int $id, array $payload): void
    {
        AuditLog::create(['actor_id' => $u->id, 'action' => $action, 'entity_type' => $type, 'entity_id' => $id, 'payload' => $payload]);
    }

    private function fail(string $m): never
    {
        throw ValidationException::withMessages(['attendance' => $m]);
    }
}
