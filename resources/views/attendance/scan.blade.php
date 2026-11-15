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
        <h2 style="font-size: 18px; margin-bottom: 8px;">Mã QR động chiếu hội trường</h2>
        <p class="notice" style="margin-bottom: 16px;">
            Chỉ mở sau khi sự kiện kết thúc. Mỗi đợt có hiệu lực tối đa 10 phút. Mã QR tự động đổi chữ ký sau mỗi 15 giây để chống chụp ảnh gửi ra ngoài.
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

        <div id="qr" style="margin: 20px auto; max-width: 100%; min-height: 280px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 16px; padding: 20px;">
            <p style="color: #64748b; font-weight: 500;">Đang kiểm tra trạng thái phiên điểm danh...</p>
        </div>

        <div style="text-align: center; margin-bottom: 20px;">
            <strong id="deadline" style="display: block; font-size: 15px; color: #1e3a8a; margin-bottom: 4px;"></strong>
            <p id="countdown" aria-live="polite" style="font-size: 18px; font-weight: 800; color: #dc2626; margin: 0;"></p>
        </div>

        <form method="POST" action="/events/{{ $event->id }}/attendance/close" onsubmit="return confirm('Bạn có chắc chắn muốn đóng và thu hồi mã điểm danh ngay lập tức?');">
            @csrf
            <button class="btn danger full">
                ⏹ Đóng và thu hồi mã QR ngay lập tức
            </button>
        </form>

        <p class="fine" style="margin-top: 16px; line-height: 1.5;">
            Lưu ý nghiệp vụ: Không chia sẻ mã QR lên mạng xã hội. BTC cần phối hợp giám sát sinh viên có mặt thực tế tại hội trường.
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

async function refresh() {
    try {
        const res = await fetch('/events/{{ $event->id }}/attendance/qr', {
            headers: { Accept: 'application/json' },
            cache: 'no-store'
        });
        if (!res.ok) throw Error();
        const d = await res.json();
        
        if (!d.active) {
            document.getElementById('qr').innerHTML = '<p style="color: #b45309; font-weight: 600;">Phiên chưa mở, đã đóng hoặc đã hết thời gian hiệu lực.</p>';
            document.getElementById('deadline').textContent = '';
            document.getElementById('countdown').textContent = '';
            expiry = 0;
            return;
        }
        
        document.getElementById('qr').innerHTML = d.svg;
        expiry = Date.parse(d.expires_at);
        offset = Date.parse(d.server_now) - Date.now();
        document.getElementById('deadline').textContent = 'Hạn phiên: ' + new Date(expiry).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' (' + new Date(expiry).toLocaleDateString('vi-VN') + ')';
    } catch {
        document.getElementById('qr').innerHTML = '<p style="color: #dc2626; font-weight: 600;">Không tải được mã. Vui lòng kiểm tra kết nối mạng.</p>';
        document.getElementById('deadline').textContent = '';
        document.getElementById('countdown').textContent = '';
        expiry = 0;
    }
}

refresh();
setInterval(refresh, 15000);

setInterval(() => {
    if (!expiry) return;
    const n = Math.max(0, Math.ceil((expiry - Date.now() - offset) / 1000));
    document.getElementById('countdown').textContent = 'Còn lại: ' + Math.floor(n / 60) + ':' + String(n % 60).padStart(2, '0');
    if (n === 0) {
        document.getElementById('qr').innerHTML = '<p style="color: #dc2626; font-weight: 600;">Đợt điểm danh 10 phút đã hết hạn.</p>';
        document.getElementById('deadline').textContent = '';
        document.getElementById('countdown').textContent = '';
        expiry = 0;
    }
}, 1000);
</script>
@endsection
