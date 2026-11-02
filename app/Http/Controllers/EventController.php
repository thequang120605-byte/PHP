<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index(Request $r)
    {
        $q = Event::with(['category', 'criteria', 'semester'])
            ->withCount(['confirmed', 'waitlist'])
            ->where('status', 'published');

        if ($r->filled('q')) {
            $q->where('title', 'like', '%'.mb_substr((string) $r->input('q'), 0, 100).'%');
        }
        if ($r->filled('category')) {
            $q->where('category_id', $r->integer('category'));
        }
        if ($r->input('open') === '1') {
            $q->where('opens_at', '<=', now())->where('closes_at', '>', now());
        }

        return view('events', [
            'events' => $q->orderBy('starts_at')->paginate(9)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function show(Event $event, Request $r)
    {
        abort_unless(
            $event->status === 'published' ||
            $r->user()?->role === 'admin' ||
            ($r->user() && $event->registrations()->where('user_id', $r->user()->id)->exists()),
            404
        );

        $reg = $r->user() ? Registration::where('event_id', $event->id)->where('user_id', $r->user()->id)->first() : null;

        return view('event', [
            'e' => $event->load(['category', 'semester', 'criteria'])->loadCount(['confirmed', 'waitlist']),
            'reg' => $reg,
        ]);
    }

    public function register(Event $event, Request $r, RegistrationService $service)
    {
        $allowWaitlist = $r->boolean('join_waitlist');

        try {
            $reg = $service->register($event, $r->user(), $allowWaitlist);
        } catch (ValidationException $error) {
            AuditLog::create([
                'actor_id' => $r->user()->id,
                'action' => 'registration.rejected',
                'entity_type' => 'event',
                'entity_id' => $event->id,
                'payload' => [
                    'received_at' => now()->toIso8601String(),
                    'reason' => $error->errors(),
                ],
            ]);
            throw $error;
        }

        $msg = $reg->status === 'waitlist'
            ? "Bạn đã được thêm vào danh sách chờ (Thứ tự: #{$reg->waitlist_position}). Khi có người hủy chỗ, hệ thống sẽ tự động xác nhận vé cho bạn."
            : 'Đã giữ chỗ. Chỉ quét QR chung khi BTC mở điểm danh cuối sự kiện.';

        // Gửi thông báo trong ứng dụng
        try {
            \App\Services\NotificationService::send(
                $user,
                $reg->status === 'waitlist' ? "Hàng chờ: {$event->title}" : "Vé giữ chỗ: {$event->title}",
                $reg->status === 'waitlist'
                    ? "Bạn đang ở vị trí #{$reg->waitlist_position} trong danh sách chờ. Hệ thống sẽ tự động đôn lên khi có chỗ."
                    : "Mã vé {$reg->reference} đã sẵn sàng. Điểm danh tại hội trường cuối sự kiện để nhận ĐRL.",
                '/registrations/'.$reg->id,
                'registration',
                'ticket'
            );
        } catch (\Throwable) {}

        return redirect('/registrations/'.$reg->id)->with('success', $msg);
    }

    public function mine(Request $r)
    {
        return view('mine', [
            'registrations' => $r->user()
                ->registrations()
                ->with(['event.category', 'event.criteria', 'event.semester'])
                ->latest('submitted_at')
                ->paginate(10),
        ]);
    }

    public function receipt(Registration $registration, Request $r)
    {
        abort_unless($r->user()->id === $registration->user_id || $r->user()->role === 'admin', 403);

        $qrSvg = null; // No permanent personal attendance QR.

        return view('receipt', [
            'reg' => $registration->load(['event.category', 'event.criteria', 'event.semester']),
            'logs' => AuditLog::where('entity_type', 'registration')->where('entity_id', $registration->id)->orderBy('id')->get(),
            'qrSvg' => $qrSvg,
        ]);
    }

    public function cancel(Registration $registration, Request $r, RegistrationService $service)
    {
        $service->cancel($registration, $r->user());

        return back()->with('success', 'Đã hủy phiếu và giải phóng chỗ. Nếu có người trong danh sách chờ, chỗ trống sẽ tự động được chuyển giao.');
    }
}
