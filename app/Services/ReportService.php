<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\StudentPoint;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Báo cáo thống kê tổng quan theo sự kiện
     */
    public function getEventReport(Event $event): array
    {
        $regs = Registration::where('event_id', $event->id);
        $totalRegistered = (clone $regs)->where('status', 'confirmed')->count();
        $waitlistCount = (clone $regs)->where('status', 'waitlist')->count();
        $cancelledCount = (clone $regs)->where('status', 'cancelled')->count();
        $confirmed = (clone $regs)->where('status', 'confirmed');
        $attendedComplete = (clone $confirmed)->whereHas('completion')->count();
        $attendedIncomplete = (clone $confirmed)->whereDoesntHave('completion')->whereIn('attendance_status', ['needs_verification', 'attended_complete', 'attended_incomplete'])->count();
        $absent = (clone $confirmed)->whereDoesntHave('completion')->where('attendance_status', 'absent')->count();

        $pointsAwarded = StudentPoint::where('event_id', $event->id)->where('status', 'approved')->sum('points');
        $studentsReceivedPoints = StudentPoint::where('event_id', $event->id)->where('status', 'approved')->count();

        return [
            'event' => $event,
            'capacity' => $event->capacity,
            'confirmed' => $totalRegistered,
            'waitlist' => $waitlistCount,
            'cancelled' => $cancelledCount,
            'attendance_rate' => $totalRegistered > 0 ? round(($attendedComplete / $totalRegistered) * 100, 1) : 0,
            'attended_complete' => $attendedComplete,
            'attended_incomplete' => $attendedIncomplete,
            'absent' => $absent,
            'points_awarded' => $pointsAwarded,
            'students_received_points' => $studentsReceivedPoints,
        ];
    }

    /**
     * Xuất danh sách đối soát sinh viên tham dự sự kiện ra file CSV chuẩn UTF-8 BOM
     */
    public function exportEventReconciliationCsv(Event $event): StreamedResponse
    {
        $filename = 'Doi_soat_'.preg_replace('/[^a-zA-Z0-9_\-]/', '_', $event->title).'_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($event) {
            $handle = fopen('php://output', 'w');
            // Thêm UTF-8 BOM để Excel hiển thị đúng tiếng Việt có dấu
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'STT',
                'MSSV',
                'Họ và tên',
                'Lớp',
                'Email',
                'Trạng thái đăng ký',
                'Trạng thái điểm danh',
                'Nguồn điểm danh',
                'Giờ vào không áp dụng',
                'Giờ ghi nhận kết thúc',
                'Điểm rèn luyện',
                'Mã phiếu đăng ký',
            ]);

            $query = Registration::where('event_id', $event->id)->with('user')->orderBy('id');
            $stt = 1;
            $query->chunkById(500, function ($batch) use ($handle, $event, &$stt) {
                $points = StudentPoint::where('event_id', $event->id)->whereIn('student_id', $batch->pluck('user_id'))->where('status', 'approved')->pluck('points', 'student_id');
                foreach ($batch as $r) {
                    $s = $r->snapshot;
                    $row = [$stt++, $s['student_code'] ?? $r->user->student_code, $s['name'] ?? $r->user->name, $s['class_name'] ?? $r->user->class_name, $s['email'] ?? $r->user->email, $r->statusLabel(), $r->attendanceLabel(), $r->attendance_source, 'Không áp dụng', $r->completed_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i:s d/m/Y') ?? 'Chưa ghi nhận', $points->get($r->user_id, 0), $r->reference];
                    fputcsv($handle, array_map(fn ($v) => $this->sanitizeCsvCell((string) $v), $row), ',', '"', '');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Báo cáo điểm rèn luyện theo học kỳ
     */
    public function getSemesterReport(Semester $semester): array
    {
        $events = Event::where('semester_id', $semester->id)->with('criteria')->get();

        $totalPointsAwarded = StudentPoint::where('semester_id', $semester->id)
            ->where('status', 'approved')
            ->sum('points');

        $totalStudents = StudentPoint::where('semester_id', $semester->id)
            ->where('status', 'approved')
            ->distinct('student_id')
            ->count('student_id');

        return [
            'semester' => $semester,
            'events_count' => $events->count(),
            'total_points' => $totalPointsAwarded,
            'total_students' => $totalStudents,
            'events' => $events,
        ];
    }

    private function sanitizeCsvCell(string $text): string
    {
        // Chống CSV Formula Injection nếu ký tự đầu là =, +, -, @
        if (preg_match('/^[=\+\-@]/', $text)) {
            return "'".$text;
        }

        return $text;
    }
}
