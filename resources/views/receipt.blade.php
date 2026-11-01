@extends('layout')

@section('title', 'Vé & Phiếu đăng ký')

@section('content')
<a href="/my-registrations">← Trở về danh sách đăng ký</a>

<div class="page-heading">
    <small class="eyebrow">VÉ ĐIỆN TỬ VÀ BẰNG CHỨNG THAM DỰ</small>
    <h1>Phiếu đăng ký và kết quả tham dự</h1>
    <p class="mono">{{ $reg->reference }}</p>
</div>

<div class="detail">
    <section class="panel">
        <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
            @if($reg->status === 'confirmed')
                <span class="badge green">Đã xác nhận chỗ</span>
            @elseif($reg->status === 'waitlist')
                <span class="badge warning">Danh sách chờ #{{ $reg->waitlist_position }}</span>
            @else
                <span class="badge danger">Đã hủy</span>
            @endif

            <span class="badge {{ $reg->attendance_status === 'attended_complete' ? 'green' : ($reg->attendance_status === 'needs_verification' ? 'warning' : '') }}">
                {{ $reg->attendanceLabel() }}
            </span>
        </div>

        <h2>{{ $reg->snapshot['event_title'] ?? $reg->event->title }}</h2>

        @if($reg->status === 'confirmed')
        <div class="notice">Cuối sự kiện, BTC mở mã QR chung trong 10 phút. Đăng nhập trên điện thoại, quét mã đang hiển thị và xác nhận. Phiếu này không phải mã điểm danh cá nhân.</div>
        <a class="btn" href="/events/{{ $reg->event_id }}/attendance/confirm">Trang xác nhận tham dự</a>
        @endif
        <p class="notice">Phiếu xác nhận đăng ký, chưa xác nhận tham dự và chưa cộng điểm.</p>
        <h3 style="margin-top: 25px;">Thông tin sinh viên</h3>
        <dl>
            @foreach(['student_code'=>'MSSV lúc gửi','name'=>'Họ tên lúc gửi','class_name'=>'Lớp','email'=>'Email','source'=>'Kênh đăng ký'] as $f=>$label)
                <dt>{{ $label }}</dt>
                <dd>{{ $reg->snapshot[$f] ?? 'N/A' }}</dd>
            @endforeach
            <dt>Thời gian đăng ký</dt>
            <dd>{{ $reg->submitted_at ? $reg->submitted_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') : 'N/A' }} VN</dd>
            <dt>Điểm danh vào</dt>
            <dd>{{ $reg->check_in_at ? $reg->check_in_at->timezone('Asia/Ho_Chi_Minh')->format('H:i:s d/m/Y') : 'Chưa điểm danh vào' }}</dd>
            <dt>Điểm danh ra</dt>
            <dd>{{ $reg->check_out_at ? $reg->check_out_at->timezone('Asia/Ho_Chi_Minh')->format('H:i:s d/m/Y') : 'Chưa điểm danh ra' }}</dd>
            <dt>Phương thức</dt>
            <dd>{{ strtoupper($reg->attendance_source) }}</dd>
        </dl>

        <div style="display: flex; gap: 12px; margin-top: 25px; flex-wrap: wrap;">
            <a class="btn secondary" href="/events/{{ $reg->event_id }}">Xem chi tiết sự kiện</a>

            @if(auth()->id() === $reg->user_id && $reg->status === 'confirmed' && $reg->event->isOpen())
                <form method="POST" action="/registrations/{{ $reg->id }}/cancel" onsubmit="return confirm('Bạn có chắc chắn muốn hủy chỗ?');">
                    @csrf
                    <button class="btn danger">Hủy đăng ký và giải phóng chỗ</button>
                </form>
            @endif

            @if($reg->attendance_status === 'needs_verification' || ($reg->status === 'confirmed' && $reg->attendance_status === 'registered' && now()->gt($reg->event->ends_at)))
                <a class="btn warning" href="/events/{{ $reg->event_id }}/disputes/create">Gửi phản hồi sai sót / Minh chứng</a>
            @endif
        </div>
    </section>

    <section class="panel">
        <h2>Nhật ký xử lý & Audit</h2>
        @forelse($logs as $log)
            <div class="timeline">
                <strong>
                    @switch($log->action)
                        @case('registration.confirmed') Xác nhận giữ chỗ @break
                        @case('registration.waitlist_joined') Vào danh sách chờ @break
                        @case('registration.waitlist_promoted') Đôn lên vé chính thức từ hàng chờ @break
                        @case('registration.cancelled') Hủy đăng ký @break
                        @case('attendance.check_in') Điểm danh vào @break
                        @case('attendance.check_out') Điểm danh ra @break
                        @default {{ $log->action }}
                    @endswitch
                </strong>
                <p>{{ $log->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }}</p>
                <small class="mono">{{ $log->payload['reference'] ?? ($log->payload['time'] ?? '') }}</small>
            </div>
        @empty
            <p class="muted">Chưa có nhật ký ghi nhận.</p>
        @endforelse
    </section>
</div>
@endsection
