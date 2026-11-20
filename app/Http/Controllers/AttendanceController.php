<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\PaperAttendance;
use App\Services\AttendanceService;
use App\Services\QrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    private function authorizeStaff(Event $e, Request $r): void
    {
        abort_unless($e->hasStaffRole($r->user(), ['scanner', 'manager']), 403);
    }

    public function showScanPage(Event $event, Request $r)
    {
        $this->authorizeStaff($event, $r);

        $recentScans = \App\Models\EventCompletion::where('event_id', $event->id)
            ->where('method', 'end_qr')
            ->with('user')
            ->latest('completed_at')
            ->limit(100)
            ->get();

        return view('attendance.scan', [
            'event' => $event,
            'session' => $this->service->current($event),
            'stats' => $this->service->reconcile($event),
            'recentScans' => $recentScans,
            'slotSeconds' => \App\Services\AttendanceService::slotDuration(),
        ]);
    }

    public function exportQrFile(Event $event, Request $r)
    {
        $this->authorizeStaff($event, $r);
        $filePath = $this->service->getOrCreateQrExportFile($event);
        $filename = 'danh-sach-quet-qr-sukien-'.$event->id.'-'.date('Ymd_His').'.csv';

        return response()->download($filePath, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function open(Event $event, Request $r)
    {
        $r->validate(['reason' => 'nullable|string|max:500']);
        $this->service->open($event, $r->user(), $r->input('reason'));

        return back()->with('success', 'Đã mở điểm danh kết thúc. Đợt hết hạn sau đúng 10 phút, không tự gia hạn.');
    }

    public function close(Event $event, Request $r)
    {
        $this->service->close($event, $r->user());

        return back()->with('success', 'Đã thu hồi mã điểm danh.');
    }

    public function qr(Event $event, Request $r)
    {
        $this->authorizeStaff($event, $r);
        $s = $this->service->current($event);
        if (! $s?->active() || $event->status !== 'published') {
            return response()->json(['active' => false])->header('Cache-Control', 'no-store');
        }

        $scannedCompletions = \App\Models\EventCompletion::where('event_id', $event->id)
            ->where('method', 'end_qr')
            ->with('user')
            ->latest('completed_at')
            ->limit(15)
            ->get();

        $recentList = $scannedCompletions->map(fn ($c) => [
            'time' => $c->completed_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i:s') ?? '',
            'student_code' => $c->user?->student_code ?? '',
            'name' => $c->user?->name ?? 'Sinh viên',
            'class_name' => $c->user?->class_name ?? '',
        ]);

        // Nếu client chỉ hỏi danh sách sinh viên vừa quét để cập nhật bảng, không sinh lại QR
        if ($r->query('only') === 'attendees') {
            return response()->json([
                'active' => true,
                'scanned_count' => \App\Models\EventCompletion::where('event_id', $event->id)->where('method', 'end_qr')->count(),
                'recent_scans' => $recentList,
            ])->withHeaders(['Cache-Control' => 'no-store, private']);
        }

        $slotDuration = \App\Services\AttendanceService::slotDuration();
        $nowTs = now()->timestamp;
        $secondsRemainingInSlot = $slotDuration - ($nowTs % $slotDuration);

        $payload = $this->service->payload($s);
        // An toàn: Token chỉ truyền qua URL Fragment (#code=), không gửi qua Query String (?code=)
        // để tuyệt đối không lưu vết vào web server access logs, reverse proxy hoặc header Referer.
        $url = url('/events/'.$event->id.'/attendance/confirm').'#code='.$payload;

        return response()->json([
            'active' => true,
            'svg' => QrService::svg($url, 340),
            'expires_at' => $s->expires_at->toIso8601String(),
            'server_now' => now()->toIso8601String(),
            'slot_seconds' => $slotDuration,
            'seconds_remaining_in_slot' => $secondsRemainingInSlot,
            'scanned_count' => \App\Models\EventCompletion::where('event_id', $event->id)->where('method', 'end_qr')->count(),
            'recent_scans' => $recentList,
        ])->withHeaders(['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer']);
    }

    public function confirm(Event $event, Request $request)
    {
        abort_unless(
            $request->user()->role === 'student',
            403
        );

        $registered = $event->registrations()
            ->where('user_id', $request->user()->id)
            ->where('status', 'confirmed')
            ->exists();

        abort_unless(
            $event->status === 'published' && $registered,
            403
        );

        $session = $this->service->current($event);
        $completion = \App\Models\EventCompletion::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->first();

        // Máy chủ KHÔNG tự sinh hoặc phản hồi token trong HTML. Token chỉ được nạp từ client
        // qua quét camera trực tiếp hoặc trích xuất từ URL Fragment (#code=).
        return response()->view('attendance.confirm', [
            'event' => $event,
            'completion' => $completion,
            'code' => '',
            'hasActiveSession' => $session?->active() ?? false,
        ])->withHeaders([
            'Cache-Control' => 'no-store, private',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function complete(Event $event, Request $r)
    {
        $d = $r->validate(['code' => 'required|string|max:150']);
        $res = $this->service->complete($event, $r->user(), $d['code']);
        $message = $res['duplicate'] ? 'Bạn đã được ghi nhận trước đó, không tạo lượt trùng.' : 'Đã ghi nhận tham dự cuối sự kiện. Điểm đề xuất đang chờ người có quyền duyệt.';
        if ($r->expectsJson()) {
            return response()->json(['message' => $message, 'duplicate' => $res['duplicate']]);
        }

        return back()->with('success', $message);
    }

    public function showPaperForm(Event $event, Request $r)
    {
        $this->authorizeStaff($event, $r);

        return view('attendance.paper', ['event' => $event, 'paperSheets' => PaperAttendance::where('event_id', $event->id)->with('user')->latest()->limit(50)->get()]);
    }

    public function storePaper(Event $event, Request $r)
    {
        $this->authorizeStaff($event, $r);
        $r->validate(['scan_file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240', 'notes' => 'required|string|max:1000', 'raw_text' => 'required|string|max:100000']);
        $entries = [];
        foreach (preg_split('/\R/', trim($r->input('raw_text'))) as $line) {
            if (! trim($line)) {
                continue;
            }$p = array_map('trim', preg_split('/[\t|,]/', $line));
            $entries[] = ['student_code' => $p[0] ?? '', 'has_check_out' => in_array(mb_strtolower($p[3] ?? ''), ['1', 'x', 'yes', 'true'], true)];
        }$path = $r->file('scan_file')->store('paper_scans', 'local');
        try {
            $this->service->importPaperAttendance($event, $entries, $path, $r->input('notes'), $r->user());
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }

        return back()->with('success', 'Đã đối soát giấy và lưu lịch sử. Không tự cấp điểm.');
    }

    public function paperFile(PaperAttendance $paper, Request $r)
    {
        abort_unless($paper->event->hasStaffRole($r->user(), ['scanner', 'approver', 'manager']), 403);
        abort_unless($paper->scan_file_path && Storage::disk('local')->exists($paper->scan_file_path), 404);

        return Storage::disk('local')->download($paper->scan_file_path);
    }

    public function reconcile(Event $event, Request $r)
    {
        $this->authorizeStaff($event, $r);
        $res = $this->service->reconcile($event);

        return back()->with('success', 'Đã đối soát: '.$res['completed'].' lượt có bằng chứng hoàn thành; không tự sửa lịch sử.');
    }
}
