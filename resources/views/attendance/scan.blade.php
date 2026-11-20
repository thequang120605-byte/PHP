@extends('layout')

@section('title', 'Mở điểm danh kết thúc · ' . $event->title)

@section('content')
<div style="margin-bottom: 16px;">
    <a href="/events/{{ $event->id }}" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #2563eb;">
        ← Quay lại trang sự kiện
    </a>
</div>

<div class="page-heading">
    <small class="eyebrow">ĐIỀU HÀNH BAN TỔ CHỨC</small>
    <h1>Mở điểm danh kết thúc sự kiện</h1>
    <p class="muted">{{ $event->title }} · Địa điểm: {{ $event->venue }}</p>
    <p style="font-size: 13.5px; color: #475569;">
        Giờ kết thúc dự kiến: <strong>{{ $event->ends_at->timezone('Asia/Ho_Chi_Minh')->format('H:i · d/m/Y') }}</strong>
    </p>
</div>

<div class="detail">
    <section class="panel">
        <h2 style="font-size: 18px; margin-bottom: 8px;">Mã QR động chiếu hội trường (Đổi mã mỗi 2 phút)</h2>
        <p class="notice" style="margin-bottom: 16px;">
            Chỉ mở sau khi sự kiện kết thúc. Mỗi đợt có hiệu lực tối đa 10 phút. <strong>Mã QR tự động đổi sau mỗi 2 phút (120 giây)</strong> và toàn bộ dữ liệu sinh viên quét thành công được tự động lưu vào 1 file CSV trên máy chủ.
        </p>

        <form method="POST" action="/events/{{ $event->id }}/attendance/open" style="margin-bottom: 20px;">
            @csrf
            <label>
                Lý do mở hoặc mở lại phiên điểm danh (nếu có):
                <input type="text" name="reason" maxlength="500" placeholder="Ví dụ: Khắc phục gián đoạn kết nối máy chiếu / mở bổ sung">
            </label>
            <button class="btn" style="padding: 12px 20px; font-size: 14.5px;">
                ▶ Mở phiên điểm danh QR kết thúc
            </button>
        </form>

        <div id="qr" style="margin: 20px auto; max-width: 100%; min-height: 280px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 16px; padding: 20px; transition: opacity 0.4s ease-in-out;">
            <p style="color: #64748b; font-weight: 500;">Đang kiểm tra trạng thái phiên điểm danh...</p>
        </div>

        <div style="text-align: center; margin-bottom: 20px;">
            <strong id="deadline" style="display: block; font-size: 15px; color: #1e3a8a; margin-bottom: 4px;"></strong>
            <p id="countdown" aria-live="polite" style="font-size: 18px; font-weight: 800; color: #dc2626; margin: 0 0 6px 0;"></p>
            <span id="qr-rotation-timer" style="font-size: 13.5px; color: #2563eb; font-weight: 700; background: #eff6ff; padding: 5px 12px; border-radius: 999px; border: 1px solid #bfdbfe; display: none;">
                🔄 Mã QR sẽ đổi sang mã mới sau: <strong id="cycle-countdown">02:00</strong>
            </span>
        </div>

        <form method="POST" action="/events/{{ $event->id }}/attendance/close" onsubmit="return confirm('Bạn có chắc chắn muốn đóng và thu hồi mã điểm danh ngay lập tức?');">
            @csrf
            <button class="btn danger full">
                ⏹ Đóng và thu hồi mã QR ngay lập tức
            </button>
        </form>

        <!-- KHỐI QUẢN LÝ FILE LƯU TRỮ DANH SÁCH QUÉT QR -->
        <div style="margin-top: 24px; padding: 18px; background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h3 style="margin: 0 0 4px 0; font-size: 15px; color: #166534; display: flex; align-items: center; gap: 6px;">
                        <span>📁</span> File lưu trữ danh sách quét QR
                    </h3>
                    <p style="margin: 0; font-size: 13px; color: #15803d;">
                        Tất cả sinh viên quét mã được tự động ghi vào: 
                        <code style="background: rgba(22, 101, 52, 0.1); padding: 2px 6px; border-radius: 4px; font-weight: 600;">event_{{ $event->id }}_danh_sach_quet_qr.csv</code>
                    </p>
                </div>
                <a href="/events/{{ $event->id }}/attendance/export-qr-csv" class="btn" style="background: #15803d; color: #ffffff; padding: 9px 16px; font-size: 13.5px; font-weight: 600; text-decoration: none; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px;">
                    📥 Tải file danh sách (.CSV)
                </a>
            </div>
        </div>

        <!-- BẢNG DANH SÁCH SINH VIÊN VỪA QUÉT QR (THỜI GIAN THỰC) -->
        <div style="margin-top: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="font-size: 16px; margin: 0; color: #0f172a;">
                    👥 Danh sách sinh viên đã quét QR (<span id="scanned-total">{{ $recentScans->count() }}</span> bạn)
                </h3>
                <span class="badge green" style="font-size: 11.5px;">Thời gian thực</span>
            </div>

            <div style="overflow-x: auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                            <th style="padding: 10px 12px; color: #64748b; font-weight: 600;">Thời gian</th>
                            <th style="padding: 10px 12px; color: #64748b; font-weight: 600;">MSSV</th>
                            <th style="padding: 10px 12px; color: #64748b; font-weight: 600;">Họ và tên</th>
                            <th style="padding: 10px 12px; color: #64748b; font-weight: 600;">Lớp</th>
                            <th style="padding: 10px 12px; color: #64748b; font-weight: 600; text-align: right;">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody id="scanned-table-body">
                        @forelse($recentScans as $scan)
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px 12px; color: #64748b; font-variant-numeric: tabular-nums;">
                                    {{ $scan->completed_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i:s') }}
                                </td>
                                <td style="padding: 10px 12px; font-weight: 600; color: #0f172a;">
                                    {{ $scan->user?->student_code ?? '---' }}
                                </td>
                                <td style="padding: 10px 12px; color: #0f172a;">
                                    {{ $scan->user?->name ?? 'Sinh viên' }}
                                </td>
                                <td style="padding: 10px 12px; color: #64748b;">
                                    {{ $scan->user?->class_name ?? '---' }}
                                </td>
                                <td style="padding: 10px 12px; text-align: right;">
                                    <span class="badge green" style="font-size: 11px;">Đã quét QR</span>
                                </td>
                            </tr>
                        @empty
                            <tr id="row-empty">
                                <td colspan="5" style="padding: 24px; text-align: center; color: #94a3b8;">
                                    Chưa có sinh viên nào quét mã trong phiên này. Danh sách sẽ cập nhật tức thì khi sinh viên quét QR.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p class="fine" style="margin-top: 16px; line-height: 1.5;">
            Lưu ý nghiệp vụ: Mã QR đổi sau mỗi 2 phút. Sinh viên cần quét mã hiển thị trên màn chiếu trước khi chuyển sang mã mới.
        </p>
    </section>

    <aside>
        <section class="panel" style="margin-bottom: 20px;">
            <h2 style="font-size: 17px; margin-bottom: 14px;">Thống kê hiện diện</h2>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-size: 13.5px;">Đã ghi nhận có mặt:</span>
                    <strong style="color: #15803d; font-size: 18px;">{{ $stats['completed'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                    <span style="color: #64748b; font-size: 13.5px;">Cần đối soát / xác minh:</span>
                    <strong style="color: #d97706; font-size: 16px;">{{ $stats['needs_verification'] }}</strong>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2 style="font-size: 17px; margin-bottom: 14px;">Công cụ ban tổ chức</h2>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="/events/{{ $event->id }}/attendance/paper" class="btn secondary full">
                    📝 Đối soát danh sách giấy
                </a>

                @if(auth()->user()->role === 'admin' || $event->hasStaffRole(auth()->user(), ['approver', 'manager']))
                    <a href="/events/{{ $event->id }}/points/review" class="btn secondary full">
                        ⚖️ Duyệt điểm rèn luyện
                    </a>
                @endif

                @if(auth()->user()->role === 'admin' || $event->hasStaffRole(auth()->user(), ['manager']))
                    <a href="/admin/events/{{ $event->id }}/participants" class="btn white full" style="border: 1px solid #cbd5e1;">
                        👥 Danh sách người tham dự
                    </a>
                @endif
            </div>
        </section>
    </aside>
</div>

<script>
let expiry = 0, offset = 0;
let slotSeconds = {{ $slotSeconds ?? 120 }};
let cycleRemaining = slotSeconds;
let isFetchingQr = false;
let isPollingAttendees = false;

// 1. Hàm nạp mã QR mới (Chỉ chạy lúc mở trang hoặc khi hết trọn vẹn 2 phút)
async function fetchNewQr() {
    if (isFetchingQr) return;
    isFetchingQr = true;
    const qrBox = document.getElementById('qr');
    
    try {
        // Hiệu ứng mờ dần nhẹ nhàng khi chuẩn bị đổi mã
        qrBox.style.opacity = '0.35';

        const res = await fetch('/events/{{ $event->id }}/attendance/qr', {
            headers: { Accept: 'application/json' },
            cache: 'no-store'
        });
        if (!res.ok) throw Error();
        const d = await res.json();
        
        if (!d.active) {
            qrBox.innerHTML = '<p style="color: #b45309; font-weight: 600;">Phiên chưa mở, đã đóng hoặc đã hết thời gian hiệu lực.</p>';
            document.getElementById('deadline').textContent = '';
            document.getElementById('countdown').textContent = '';
            document.getElementById('qr-rotation-timer').style.display = 'none';
            expiry = 0;
            qrBox.style.opacity = '1';
            return;
        }
        
        // Cập nhật SVG và làm rõ lại từ từ (êm dịu, không giật màn hình)
        qrBox.innerHTML = d.svg;
        setTimeout(() => { qrBox.style.opacity = '1'; }, 60);

        expiry = Date.parse(d.expires_at);
        offset = Date.parse(d.server_now) - Date.now();
        if (d.slot_seconds) slotSeconds = d.slot_seconds;

        // Đồng bộ chuẩn xác thời gian còn lại của chu kỳ 2 phút từ server
        if (typeof d.seconds_remaining_in_slot !== 'undefined' && d.seconds_remaining_in_slot > 0) {
            cycleRemaining = d.seconds_remaining_in_slot;
        } else {
            cycleRemaining = slotSeconds;
        }

        document.getElementById('deadline').textContent = 'Hạn phiên: ' + new Date(expiry).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' (' + new Date(expiry).toLocaleDateString('vi-VN') + ')';
        document.getElementById('qr-rotation-timer').style.display = 'inline-block';

        renderAttendees(d);
    } catch {
        qrBox.innerHTML = '<p style="color: #dc2626; font-weight: 600;">Không tải được mã. Vui lòng kiểm tra kết nối mạng.</p>';
        document.getElementById('deadline').textContent = '';
        document.getElementById('countdown').textContent = '';
        document.getElementById('qr-rotation-timer').style.display = 'none';
        expiry = 0;
        qrBox.style.opacity = '1';
    } finally {
        isFetchingQr = false;
    }
}

// 2. Hàm chỉ cập nhật danh sách sinh viên vừa quét (CHẠY NGẦM, TUYỆT ĐỐI KHÔNG ĐỤNG VÀO MÃ QR VÀ KHÔNG RESET 2 PHÚT)
async function pollAttendeesOnly() {
    if (isPollingAttendees || !expiry) return;
    isPollingAttendees = true;
    try {
        const res = await fetch('/events/{{ $event->id }}/attendance/qr?only=attendees', {
            headers: { Accept: 'application/json' },
            cache: 'no-store'
        });
        if (!res.ok) return;
        const d = await res.json();
        renderAttendees(d);
    } catch {
        // bỏ qua lỗi polling ngầm
    } finally {
        isPollingAttendees = false;
    }
}

// Hàm render bảng danh sách sinh viên quét mã
function renderAttendees(d) {
    if (typeof d.scanned_count !== 'undefined') {
        document.getElementById('scanned-total').textContent = d.scanned_count;
    }
    if (d.recent_scans && d.recent_scans.length > 0) {
        const tbody = document.getElementById('scanned-table-body');
        const emptyRow = document.getElementById('row-empty');
        if (emptyRow) emptyRow.remove();

        tbody.innerHTML = d.recent_scans.map(s => `
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 12px; color: #64748b; font-variant-numeric: tabular-nums;">${s.time}</td>
                <td style="padding: 10px 12px; font-weight: 600; color: #0f172a;">${s.student_code || '---'}</td>
                <td style="padding: 10px 12px; color: #0f172a;">${s.name}</td>
                <td style="padding: 10px 12px; color: #64748b;">${s.class_name || '---'}</td>
                <td style="padding: 10px 12px; text-align: right;">
                    <span class="badge green" style="font-size: 11px;">Đã quét QR</span>
                </td>
            </tr>
        `).join('');
    }
}

// Nạp mã QR ban đầu
fetchNewQr();

// 3. Đếm ngược từng giây chuẩn xác (1000ms)
setInterval(() => {
    if (!expiry) return;
    const now = Date.now();
    const sessionRemaining = Math.max(0, Math.ceil((expiry - now - offset) / 1000));
    document.getElementById('countdown').textContent = 'Còn lại phiên: ' + Math.floor(sessionRemaining / 60) + ':' + String(sessionRemaining % 60).padStart(2, '0');
    
    if (sessionRemaining === 0) {
        document.getElementById('qr').innerHTML = '<p style="color: #dc2626; font-weight: 600;">Đợt điểm danh 10 phút đã hết hạn.</p>';
        document.getElementById('deadline').textContent = '';
        document.getElementById('countdown').textContent = '';
        document.getElementById('qr-rotation-timer').style.display = 'none';
        expiry = 0;
        return;
    }

    // Đếm ngược đều đặn từng giây cho chu kỳ 2 phút (từ 02:00 -> 00:00)
    cycleRemaining = Math.max(0, cycleRemaining - 1);
    const mins = Math.floor(cycleRemaining / 60);
    const secs = String(cycleRemaining % 60).padStart(2, '0');
    document.getElementById('cycle-countdown').textContent = `${String(mins).padStart(2, '0')}:${secs}`;

    // ĐÚNG KHI ĐẾM HẾT 2 PHÚT (VỀ 0) MỚI TỰ ĐỘNG ĐỔI MÃ QR MỚI TỪ TỪ
    if (cycleRemaining === 0) {
        cycleRemaining = slotSeconds;
        fetchNewQr();
    }
}, 1000);

// 4. Polling danh sách sinh viên vừa quét mỗi 8 giây (hoàn toàn chạy ngầm)
setInterval(pollAttendeesOnly, 8000);
</script>
@endsection
