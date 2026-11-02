<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function register(Event $event, User $user, bool $allowWaitlist = false): Registration
    {
        return DB::transaction(function () use ($event, $user, $allowWaitlist) {
            // Luôn khóa sự kiện trước: đăng ký, hủy và sửa sức chứa dùng cùng thứ tự khóa.
            $e = Event::lockForUpdate()->findOrFail($event->id);
            if (! $e->isOpen()) {
                $this->fail('Không trong thời gian mở đăng ký hoặc sự kiện đã hủy.');
            }
            if (! $user->is_active || $user->role !== 'student') {
                $this->fail('Chỉ tài khoản sinh viên đang hoạt động được đăng ký.');
            }
            if (! $user->student_code || ! $user->class_name) {
                $this->fail('Hãy cập nhật MSSV và lớp trong hồ sơ.');
            }

            $old = Registration::where('event_id', $e->id)->where('user_id', $user->id)->first();
            if ($old?->status === 'confirmed') {
                $this->fail('Bạn đã đăng ký sự kiện này.');
            }
            if ($old?->status === 'waitlist') {
                $this->fail('Bạn đã có tên trong danh sách chờ của sự kiện này (# '.$old->waitlist_position.').');
            }

            $confirmedCount = $e->confirmed()->count();
            $isFull = $confirmedCount >= $e->capacity;

            if ($isFull && ! $allowWaitlist) {
                $this->fail('Sự kiện đã hết chỗ.');
            }

            $snapshot = [
                'name' => $user->name,
                'student_code' => $user->student_code,
                'class_name' => $user->class_name,
                'email' => $user->email,
                'source' => 'website',
                'event_title' => $e->title,
                'opens_at' => $e->opens_at->toIso8601String(),
                'closes_at' => $e->closes_at->toIso8601String(),
                'event_updated_at' => $e->updated_at->toIso8601String(),
                'proposed_points' => $e->proposed_points,
            ];

            if ($isFull) {
                // Vào danh sách chờ
                $maxWaitlist = Registration::where('event_id', $e->id)
                    ->where('status', 'waitlist')
                    ->max('waitlist_position') ?? 0;
                $position = $maxWaitlist + 1;

                $r = Registration::updateOrCreate(
                    ['event_id' => $e->id, 'user_id' => $user->id],
                    [
                        'reference' => (string) Str::uuid(),
                        'qr_token' => null, // Chưa có vé QR khi ở hàng chờ
                        'status' => 'waitlist',
                        'attendance_status' => 'registered',
                        'is_waitlist' => true,
                        'waitlist_position' => $position,
                        'submitted_at' => now(),
                        'cancelled_at' => null,
                        'snapshot' => $snapshot,
                    ]
                );

                AuditLog::create([
                    'actor_id' => $user->id,
                    'action' => 'registration.waitlist_joined',
                    'entity_type' => 'registration',
                    'entity_id' => $r->id,
                    'payload' => [
                        'reference' => $r->reference,
                        'waitlist_position' => $position,
                        'snapshot' => $snapshot,
                        'received_at' => now()->toIso8601String(),
                    ],
                ]);

                return $r;
            }

            // Đăng ký chính thức
            $qrToken = null; // Attendance QR is generated only at event end.
            $r = Registration::updateOrCreate(
                ['event_id' => $e->id, 'user_id' => $user->id],
                [
                    'reference' => (string) Str::uuid(),
                    'qr_token' => $qrToken,
                    'status' => 'confirmed',
                    'attendance_status' => 'registered',
                    'is_waitlist' => false,
                    'waitlist_position' => null,
                    'submitted_at' => now(),
                    'cancelled_at' => null,
                    'snapshot' => $snapshot,
                ]
            );

            AuditLog::create([
                'actor_id' => $user->id,
                'action' => 'registration.confirmed',
                'entity_type' => 'registration',
                'entity_id' => $r->id,
                'payload' => [
                    'reference' => $r->reference,
                    'snapshot' => $snapshot,
                    'received_at' => now()->toIso8601String(),
                ],
            ]);

            return $r;
        }, 3);
    }

    public function cancel(Registration $registration, User $user): void
    {
        abort_unless($registration->user_id === $user->id, 403);

        DB::transaction(function () use ($registration, $user) {
            $e = Event::lockForUpdate()->findOrFail($registration->event_id);
            $r = Registration::lockForUpdate()->findOrFail($registration->id);

            if ($r->status === 'cancelled') {
                return;
            }

            if (! $e->isOpen()) {
                $this->fail('Chỉ được hủy trong thời gian mở đăng ký.');
            }

            $wasConfirmed = ($r->status === 'confirmed');
            $r->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'waitlist_position' => null,
            ]);

            AuditLog::create([
                'actor_id' => $user->id,
                'action' => 'registration.cancelled',
                'entity_type' => 'registration',
                'entity_id' => $r->id,
                'payload' => [
                    'reference' => $r->reference,
                    'received_at' => now()->toIso8601String(),
                ],
            ]);

            // Nếu người hủy có vé confirmed, tự động đôn người đứng đầu danh sách chờ (Waitlist Promotion)
            if ($wasConfirmed) {
                $nextInLine = Registration::lockForUpdate()
                    ->where('event_id', $e->id)
                    ->where('status', 'waitlist')
                    ->orderBy('waitlist_position', 'asc')
                    ->first();

                if ($nextInLine) {
                    $newQr = null;
                    $nextInLine->update([
                        'status' => 'confirmed',
                        'is_waitlist' => false,
                        'waitlist_position' => null,
                        'reference' => (string) Str::uuid(),
                        'qr_token' => $newQr,
                        'submitted_at' => now(),
                    ]);

                    AuditLog::create([
                        'actor_id' => $nextInLine->user_id,
                        'action' => 'registration.waitlist_promoted',
                        'entity_type' => 'registration',
                        'entity_id' => $nextInLine->id,
                        'payload' => [
                            'reference' => $nextInLine->reference,
                            'promoted_at' => now()->toIso8601String(),
                            'event_title' => $e->title,
                        ],
                    ]);

                    try {
                        \App\Services\NotificationService::send(
                            $nextInLine->user_id,
                            "Được đôn lên vé chính thức: {$e->title}",
                            "Bạn vừa được nâng lên vé chính thức từ danh sách chờ cho sự kiện \"{$e->title}\". Mã vé: {$nextInLine->reference}.",
                            '/registrations/'.$nextInLine->id,
                            'registration',
                            'ticket'
                        );
                    } catch (\Throwable) {}

                    // Đánh lại số thứ tự những người còn lại trong waitlist
                    $remainingWaitlist = Registration::where('event_id', $e->id)
                        ->where('status', 'waitlist')
                        ->orderBy('waitlist_position', 'asc')
                        ->get();

                    $pos = 1;
                    foreach ($remainingWaitlist as $waitItem) {
                        $waitItem->update(['waitlist_position' => $pos++]);
                    }
                }
            }
        }, 3);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['registration' => $message]);
    }
}
