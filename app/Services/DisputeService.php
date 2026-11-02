<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DisputeService
{
    /**
     * Sinh viên gửi phản hồi / khiếu nại sai sót
     */
    public function createTicket(User $student, Event $event, array $payload, ?string $evidencePath = null): Dispute
    {
        return DB::transaction(function () use ($student, $event, $payload, $evidencePath) {
            if (! $event->isDisputeOpen()) {
                $this->fail('Thời hạn tiếp nhận phản hồi sai sót cho sự kiện này đã kết thúc.');
            }

            $type = $payload['type'] ?? 'missing_attendance';
            $description = trim($payload['description'] ?? '');

            if (! $description) {
                $this->fail('Vui lòng mô tả chi tiết lý do và nội dung phản hồi.');
            }

            $reg = Registration::where('event_id', $event->id)
                ->where('user_id', $student->id)
                ->first();

            // Sinh mã ticket duy nhất
            $ticketCode = 'FB-'.(string) Str::ulid();

            $dispute = Dispute::create([
                'ticket_code' => $ticketCode,
                'event_id' => $event->id,
                'user_id' => $student->id,
                'registration_id' => $reg?->id,
                'type' => $type,
                'description' => $description,
                'evidence_file_path' => $evidencePath,
                'status' => 'pending',
            ]);

            AuditLog::create([
                'actor_id' => $student->id,
                'action' => 'dispute.created',
                'entity_type' => 'dispute',
                'entity_id' => $dispute->id,
                'payload' => [
                    'ticket_code' => $ticketCode,
                    'type' => $type,
                    'event_title' => $event->title,
                ],
            ]);

            return $dispute;
        });
    }

    /**
     * Ban tổ chức / Admin xử lý ticket phản hồi
     */
    public function processTicket(Dispute $dispute, string $status, string $resolutionNotes, User $handler): Dispute
    {
        return DB::transaction(function () use ($dispute, $status, $resolutionNotes, $handler) {
            abort_unless($handler->is_active && $dispute->event->hasStaffRole($handler, ['approver', 'manager']), 403);
            $dispute = Dispute::lockForUpdate()->findOrFail($dispute->id);
            $validStatuses = ['pending', 'in_review', 'resolved', 'rejected'];
            if (! in_array($status, $validStatuses)) {
                $this->fail('Trạng thái xử lý không hợp lệ.');
            }

            $resolutionNotes = trim($resolutionNotes);
            if (! $resolutionNotes) {
                $this->fail('Vui lòng nhập ghi chú kết quả giải quyết khi phản hồi sinh viên.');
            }

            $dispute->update([
                'status' => $status,
                'resolution_notes' => $resolutionNotes,
                'handled_by' => $handler->id,
                'handled_at' => now(),
            ]);

            // Resolving a ticket is not attendance evidence. Use the paper review flow for corrections.

            AuditLog::create([
                'actor_id' => $handler->id,
                'action' => 'dispute.processed',
                'entity_type' => 'dispute',
                'entity_id' => $dispute->id,
                'payload' => [
                    'ticket_code' => $dispute->ticket_code,
                    'new_status' => $status,
                    'resolution_notes' => $resolutionNotes,
                ],
            ]);

            try {
                $statusText = match($status) {
                    'resolved' => 'Đã chấp thuận giải quyết',
                    'rejected' => 'Đã từ chối',
                    'in_review' => 'Đang đối soát',
                    default => $status
                };
                NotificationService::send(
                    $dispute->user_id,
                    "Phản hồi khiếu nại #{$dispute->ticket_code}",
                    "Yêu cầu cho \"{$dispute->event->title}\" đã được cập nhật: {$statusText}. Ghi chú BTC: {$resolutionNotes}",
                    '/my-disputes',
                    'dispute',
                    'check'
                );
            } catch (\Throwable) {}

            return $dispute->fresh();
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['dispute' => $message]);
    }
}
