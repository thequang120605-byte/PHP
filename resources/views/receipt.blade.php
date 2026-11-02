@extends('layout')

@section('title', 'Vé & Phiếu đăng ký · ' . ($reg->snapshot['event_title'] ?? $reg->event->title))

@section('content')
<div style="margin-bottom: 20px;">
    <a href="/my-registrations" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #2563eb;">
        ← Trở về danh sách đăng ký của tôi
    </a>
</div>

<div class="page-heading">
    <small class="eyebrow">PHIẾU ĐĂNG KÝ VÀ VÉ ĐIỆN TỬ VIP</small>
    <h1>Phiếu tham dự & Kết quả đối soát</h1>
    <p class="mono" style="font-size: 14px; color: #2563eb; font-weight: 700;">Mã vé: {{ $reg->reference }}</p>
</div>

<div class="detail">
    <div>
        <!-- THẺ VÉ VIP / APPLE WALLET PASS -->
        <div class="ticket-pass" id="printable-ticket">
            <!-- Header vé -->
            <div class="ticket-pass-header">
                <div>
                    <span class="ticket-pass-title">HUIT EVENTPASS · VIP TICKET</span>
                    <h2 style="margin: 6px 0 0; color: #ffffff; font-size: 20px; font-weight: 800;">
                        {{ $reg->snapshot['event_title'] ?? $reg->event->title }}
                    </h2>
                </div>
                <div style="text-align: right; z-index: 2;">
                    @if($reg->status === 'confirmed')
                        <span class="badge green" style="box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);">✓ Đã xác nhận chỗ</span>
                    @elseif($reg->status === 'waitlist')
                        <span class="badge warning">Danh sách chờ #{{ $reg->waitlist_position }}</span>
                    @else
                        <span class="badge danger">Đã hủy</span>
                    @endif
                </div>
            </div>

            <!-- Đường cắt răng cưa phong cách Boarding Pass -->
            <div class="ticket-perforation">
                <div class="ticket-notch-left"></div>
                <div class="ticket-cut-line"></div>
                <div class="ticket-notch-right"></div>
            </div>

            <!-- Thân vé -->
            <div class="ticket-pass-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; margin-bottom: 20px;">
                    <div>
                        <small class="muted" style="display: block; font-size: 11px; text-transform: uppercase;">Sinh viên tham dự</small>
                        <strong style="font-size: 16px; color: #0f172a;">{{ $reg->snapshot['name'] ?? auth()->user()->name }}</strong>
                        <div style="font-size: 12.5px; color: #64748b; font-family: monospace;">MSSV: {{ $reg->snapshot['student_code'] ?? 'N/A' }} · Lớp: {{ $reg->snapshot['class_name'] ?? 'N/A' }}</div>
                    </div>

                    <div>
                        <small class="muted" style="display: block; font-size: 11px; text-transform: uppercase;">Địa điểm tổ chức</small>
                        <strong style="font-size: 16px; color: #0f172a;">{{ $reg->event->venue }}</strong>
                        <div style="font-size: 12.5px; color: #64748b;">Đại học Công Thương TP.HCM</div>
                    </div>

                    <div>
                        <small class="muted" style="display: block; font-size: 11px; text-transform: uppercase;">Thời gian bắt đầu</small>
                        <strong style="color: #1e40af; font-size: 14.5px;">{{ $reg->event->starts_at->timezone('Asia/Ho_Chi_Minh')->format('H:i · d/m/Y') }}</strong>
                    </div>

                    <div>
                        <small class="muted" style="display: block; font-size: 11px; text-transform: uppercase;">Thời gian kết thúc</small>
                        <strong style="color: #1e40af; font-size: 14.5px;">{{ $reg->event->ends_at->timezone('Asia/Ho_Chi_Minh')->format('H:i · d/m/Y') }}</strong>
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: gap: 8px;">
                        <div>
                            <span class="muted" style="font-size: 11px; text-transform: uppercase;">Trạng thái điểm danh:</span>
                            <span class="badge {{ $reg->attendance_status === 'attended_complete' ? 'green' : ($reg->attendance_status === 'needs_verification' ? 'warning' : '') }}" style="margin-left: 6px;">
                                {{ $reg->attendanceLabel() }}
                            </span>
                        </div>
                        <div>
                            <span class="muted" style="font-size: 11px; text-transform: uppercase;">Điểm đề xuất:</span>
                            <strong style="color: #15803d; font-size: 15px; margin-left: 4px;">+{{ $reg->event->proposed_points }} ĐRL</strong>
                        </div>
                    </div>
                </div>

                <!-- Mã vạch giả lập Barcode SVG chuẩn chuyên nghiệp -->
                <div class="ticket-barcode-box">
                    <svg viewBox="0 0 280 40" style="width: 280px; height: 40px; max-width: 100%;" fill="#0f172a">
                        @for($i = 0; $i < 65; $i++)
                            @php $w = ($i % 3 === 0 || $i % 7 === 0) ? 3 : (($i % 2 === 0) ? 2 : 1); @endphp
                            <rect x="{{ $i * 4.3 }}" y="0" width="{{ $w }}" height="40"/>
                        @endfor
                    </svg>
                    <code style="font-size: 12px; letter-spacing: 2px; color: #475569;">{{ substr($reg->reference, 0, 8) }}-{{ substr($reg->reference, 9, 4) }}-PASS</code>
                </div>
            </div>
        </div>

        <p class="notice" style="margin-bottom: 20px;">Phiếu xác nhận đăng ký, chưa xác nhận tham dự và chưa cộng điểm.</p>

        <!-- Các nút thao tác vé -->
        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px;">
            <button type="button" class="btn white" onclick="window.print()" style="border: 1px solid #cbd5e1;">
                🖨️ In / Tải vé điện tử (PDF)
            </button>

            @if($reg->status === 'confirmed')
                <a class="btn" href="/events/{{ $reg->event_id }}/attendance/confirm">
                    Trang xác nhận tham dự cuối sự kiện →
                </a>
            @endif

            <a class="btn secondary" href="/events/{{ $reg->event_id }}">
                Xem trang sự kiện
            </a>

            @if(auth()->id() === $reg->user_id && $reg->status === 'confirmed' && $reg->event->isOpen())
                <form method="POST" action="/registrations/{{ $reg->id }}/cancel" onsubmit="return confirm('Bạn có chắc chắn muốn hủy chỗ?');" style="margin: 0;">
                    @csrf
                    <button class="btn danger">Hủy vé giữ chỗ</button>
                </form>
            @endif

            @if($reg->attendance_status === 'needs_verification' || ($reg->status === 'confirmed' && $reg->attendance_status === 'registered' && now()->gt($reg->event->ends_at)))
                <a class="btn warning" href="/events/{{ $reg->event_id }}/disputes/create">Gửi phản hồi sai sót / Nộp minh chứng</a>
            @endif
        </div>
    </div>

    <!-- Cột bên: Lịch sử xử lý & Audit trail -->
    <aside>
        <section class="panel" style="margin-bottom: 20px;">
            <h3 style="font-size: 16px; margin-bottom: 12px;">Thông tin chi tiết</h3>
            <dl style="grid-template-columns: 130px 1fr; margin: 0; padding: 0; border: 0;">
                <dt>Kênh gửi:</dt>
                <dd>{{ strtoupper($reg->snapshot['source'] ?? 'Portal') }}</dd>
                <dt>Thời gian gửi:</dt>
                <dd>{{ $reg->submitted_at ? $reg->submitted_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') : 'N/A' }}</dd>
                <dt>Giờ điểm danh:</dt>
                <dd>{{ $reg->completed_at ? $reg->completed_at->timezone('Asia/Ho_Chi_Minh')->format('H:i:s d/m/Y') : ($reg->check_out_at ? $reg->check_out_at->timezone('Asia/Ho_Chi_Minh')->format('H:i:s d/m/Y') : 'Chưa điểm danh') }}</dd>
            </dl>
        </section>

        <section class="panel">
            <h3 style="font-size: 16px; margin-bottom: 14px;">Nhật ký xử lý & Audit</h3>
            @forelse($logs as $log)
                <div class="timeline">
                    <strong style="color: #0f172a;">
                        @switch($log->action)
                            @case('registration.confirmed') Xác nhận giữ chỗ @break
                            @case('registration.waitlist_joined') Vào danh sách chờ @break
                            @case('registration.waitlist_promoted') Đôn lên vé chính thức từ hàng chờ @break
                            @case('registration.cancelled') Hủy đăng ký @break
                            @case('attendance.check_in') Điểm danh vào @break
                            @case('attendance.check_out') Điểm danh ra @break
                            @case('attendance.completed') Hoàn thành điểm danh cuối sự kiện @break
                            @default {{ $log->action }}
                        @endswitch
                    </strong>
                    <p style="font-size: 12px; color: #64748b; margin: 2px 0;">{{ $log->created_at->timezone('Asia/Ho_Chi_Minh')->format('H:i:s · d/m/Y') }}</p>
                    <small class="mono">{{ $log->payload['reference'] ?? ($log->payload['time'] ?? '') }}</small>
                </div>
            @empty
                <p class="muted">Chưa có nhật ký ghi nhận.</p>
            @endforelse
        </section>
    </aside>
</div>
@endsection
