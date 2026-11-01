<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use App\Models\Event;
use App\Services\DisputeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DisputeController extends Controller
{
    public function __construct(protected DisputeService $disputeService) {}

    /**
     * Màn hình sinh viên tạo phản hồi sai sót cho sự kiện
     */
    public function create(Event $event, Request $r)
    {
        $user = $r->user();
        $registration = $event->registrations()->where('user_id', $user->id)->first();

        return view('disputes.create', compact('event', 'registration'));
    }

    /**
     * Sinh viên nộp form phản hồi sai sót
     */
    public function store(Request $r, Event $event)
    {
        $r->validate([
            'type' => 'required|in:missing_attendance,missing_checkout,missing_points,incorrect_points',
            'description' => 'required|string|max:2000',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        $evidencePath = null;
        if ($r->hasFile('evidence')) {
            $evidencePath = $r->file('evidence')->store('dispute_evidences', 'local');
        }

        try {
            $ticket = $this->disputeService->createTicket($r->user(), $event, $r->only(['type', 'description']), $evidencePath);
        } catch (\Throwable $error) {
            if ($evidencePath) {
                Storage::disk('local')->delete($evidencePath);
            }throw $error;
        }

        return redirect('/my-disputes')->with('success', "Đã gửi phản hồi thành công! Mã yêu cầu: {$ticket->ticket_code}. Ban tổ chức sẽ đối chiếu và cập nhật trạng thái.");
    }

    /**
     * Danh sách phản hồi sai sót của sinh viên
     */
    public function myDisputes(Request $r)
    {
        $disputes = Dispute::where('user_id', $r->user()->id)
            ->with(['event', 'handler'])
            ->latest('id')
            ->paginate(15);

        return view('disputes.mine', compact('disputes'));
    }

    /**
     * Màn hình quản trị tiếp nhận phản hồi sai sót (Admin / BTC)
     */
    public function adminIndex(Request $r)
    {
        $status = $r->input('status');
        $eventId = $r->input('event_id');

        $query = Dispute::with(['event', 'user', 'registration', 'handler'])->latest('id');

        if ($status) {
            $query->where('status', $status);
        }
        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $disputes = $query->paginate(20);
        $events = Event::orderBy('id', 'desc')->get();

        return view('admin.disputes', compact('disputes', 'events', 'status', 'eventId'));
    }

    /**
     * Xử lý ticket phản hồi
     */
    public function process(Request $r, Dispute $dispute)
    {
        $r->validate([
            'status' => 'required|in:pending,in_review,resolved,rejected',
            'resolution_notes' => 'required|string|max:1000',
        ]);

        $this->disputeService->processTicket(
            $dispute,
            $r->input('status'),
            $r->input('resolution_notes'),
            $r->user()
        );

        return back()->with('success', "Đã cập nhật trạng thái ticket {$dispute->ticket_code} thành: {$r->input('status')}.");
    }

    public function evidence(Dispute $dispute, Request $r)
    {
        abort_unless($r->user()->id === $dispute->user_id || $dispute->event->hasStaffRole($r->user(), ['approver', 'manager']), 403);
        $disk = Storage::disk('local');
        abort_unless($dispute->evidence_file_path && $disk->exists($dispute->evidence_file_path), 404);

        return $disk->download($dispute->evidence_file_path);
    }
}
