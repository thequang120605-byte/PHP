@extends('layout')

@section('title', 'Xác nhận tham dự · ' . $event->title)

@section('content')
<div style="max-width: 600px; margin: 20px auto 50px;">
    <a href="/events/{{ $event->id }}" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #2563eb; margin-bottom: 16px;">
        ← Quay lại thông tin sự kiện
    </a>

    <section class="panel auth" style="max-width: 100%; margin: 0; padding: 36px 32px;">
        <!-- Brand Header HUIT -->
        <div class="auth-brand">
            <img src="{{ asset('images/logo-huit.png') }}" alt="Logo HUIT">
            <div class="auth-brand-text">
                <span class="auth-brand-sub">ĐẠI HỌC CÔNG THƯƠNG TP.HCM</span>
                <span class="auth-brand-title">Điểm danh kết thúc sự kiện</span>
            </div>
        </div>

        <h1 style="font-size: 22px; margin-bottom: 6px;">Xác nhận tham dự</h1>
        <h2 style="font-size: 16px; font-weight: 600; color: #2563eb; margin-bottom: 14px;">{{ $event->title }}</h2>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; font-size: 13.5px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>Sinh viên điểm danh:</span>
                <strong style="color: #0f172a;">{{ auth()->user()->name }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                <span>Mã số sinh viên (MSSV):</span>
                <code style="font-weight: 700; color: #1d4ed8;">{{ auth()->user()->student_code ?? 'N/A' }}</code>
            </div>
        </div>

        <!-- Trạng thái nhận diện mã (Chính xác: thông báo nhận chuỗi và cần gửi lên server xác minh) -->
        <div id="detected-banner" style="display: none; background: #eff6ff; border: 1.5px solid #93c5fd; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 22px;">📋</span>
                <div>
                    <strong style="color: #1e40af; display: block; font-size: 14px;">Đã nhận mã từ camera / liên kết quét!</strong>
                    <span style="color: #2563eb; font-size: 12.5px;">Vui lòng bấm nút bên dưới để gửi lên máy chủ xác minh chữ ký và thời hạn.</span>
                </div>
            </div>
        </div>

        <!-- Form xác nhận chính -->
        <form id="attendance-form" method="POST" action="/events/{{ $event->id }}/attendance/complete">
            @csrf
            
            <!-- Hidden input lưu mã QR từ camera/URL -->
            <input type="hidden" id="code" name="code" value="{{ old('code', $code) }}">

            <!-- Các phương án lấy mã điểm danh khi chưa có mã -->
            <div id="no-code-options" style="display: {{ empty($code) ? 'block' : 'none' }}; margin-bottom: 20px;">
                <p style="font-size: 13.5px; color: #475569; margin-bottom: 16px; line-height: 1.6;">
                    Cuối sự kiện, BTC sẽ chiếu <strong>mã QR động</strong> lên máy chiếu hội trường. Hãy quét mã đang hiển thị để xác nhận:
                </p>

                <!-- Trạng thái đợt điểm danh -->
                @if($hasActiveSession)
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px; color: #166534; display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-block; width: 8px; height: 8px; background: #16a34a; border-radius: 50%; box-shadow: 0 0 8px #16a34a;"></span>
                        <strong>Đợt điểm danh đang mở tại hội trường (hiệu lực trong 10 phút).</strong>
                    </div>
                @else
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px; color: #b45309;">
                        <strong>Chưa có đợt điểm danh nào đang mở</strong> hoặc đợt trước đã hết hạn. Hãy theo dõi máy chiếu khi BTC thông báo mở đợt.
                    </div>
                @endif

                <!-- Phương án A: Quét trực tiếp bằng Camera trên trình duyệt -->
                <div style="border: 1.5px dashed #cbd5e1; border-radius: 14px; padding: 18px; text-align: center; background: #ffffff; margin-bottom: 16px;">
                    <strong style="display: block; font-size: 14px; color: #1e293b; margin-bottom: 6px;">
                        📷 Quét mã QR bằng Camera
                    </strong>
                    <p style="font-size: 12.5px; color: #64748b; margin-bottom: 12px;">
                        Bật camera và hướng về phía màn chiếu hội trường để nhận mã tự động.
                    </p>
                    <button type="button" class="btn secondary" id="btn-toggle-camera" onclick="toggleCameraScanner()">
                        Bật Camera quét mã ngay
                    </button>

                    <!-- Khung xem Video Camera -->
                    <div id="camera-container" style="display: none; margin-top: 16px;">
                        <video id="qr-video" style="width: 100%; max-height: 260px; border-radius: 12px; background: #000; object-fit: cover;" playsinline></video>
                        <p id="camera-status" style="font-size: 12px; color: #64748b; margin-top: 8px;">Đang tìm kiếm mã QR...</p>
                    </div>
                </div>

                <!-- Phương án B: Nhập mã dự phòng (Nếu camera bị lỗi) -->
                <details style="font-size: 13px; color: #64748b; text-align: left;">
                    <summary style="cursor: pointer; font-weight: 600; color: #2563eb; margin-bottom: 8px;">
                        Nhập hoặc dán mã thủ công (dự phòng sự cố camera)
                    </summary>
                    <div style="padding-top: 8px;">
                        <input type="text" id="manual-code-input" placeholder="Dán mã điểm danh vào đây..." style="font-size: 13px; font-family: monospace;" oninput="onManualInput(this.value)">
                    </div>
                </details>
            </div>

            <!-- Nút gửi xác nhận -->
            <button type="submit" id="btn-submit-attendance" class="btn full" style="font-size: 15px; padding: 14px; margin-top: 10px;" {{ empty($code) ? 'disabled' : '' }}>
                Xác nhận tôi có mặt cuối sự kiện
            </button>
        </form>

        <p class="fine" style="margin-top: 20px; line-height: 1.5;">
            Lưu ý: Hệ thống chỉ xác nhận cho tài khoản sinh viên đang đăng nhập và có đăng ký chính thức. Mỗi mã QR chỉ có hiệu lực ngắn (15–30 giây). Điểm đề xuất sẽ được người có quyền duyệt chính thức.
        </p>

        <div style="text-align: center; margin-top: 16px;">
            <a href="/my-registrations" style="font-size: 13px; font-weight: 600; color: #64748b;">
                Xem danh sách đăng ký của tôi →
            </a>
        </div>
    </section>
</div>

<script>
// 1. Kiểm tra mã từ URL query hoặc hash
function checkUrlCode() {
    let code = '';
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('code')) {
        code = urlParams.get('code');
    }
    if (!code && window.location.hash) {
        const hashParams = new URLSearchParams(window.location.hash.slice(1));
        if (hashParams.get('code')) {
            code = hashParams.get('code');
        }
    }
    
    if (code) {
        applyCode(code);
        history.replaceState(null, '', window.location.pathname);
    }
}

// 2. Điền mã và hiển thị trạng thái sẵn sàng
function applyCode(code) {
    if (!code) return;
    document.getElementById('code').value = code;
    document.getElementById('detected-banner').style.display = 'block';
    document.getElementById('no-code-options').style.display = 'none';
    const submitBtn = document.getElementById('btn-submit-attendance');
    submitBtn.removeAttribute('disabled');
    submitBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
    submitBtn.innerHTML = '✓ Đã nhận mã · Bấm xác nhận ngay';
}

// 3. Nhập tay thủ công
function onManualInput(val) {
    if (val.trim()) {
        document.getElementById('code').value = val.trim();
        document.getElementById('btn-submit-attendance').removeAttribute('disabled');
    }
}

// 4. Trình quét Camera Scanner HTML5 (BarcodeDetector native API)
let videoStream = null;
let scanningInterval = null;

async function toggleCameraScanner() {
    const container = document.getElementById('camera-container');
    const toggleBtn = document.getElementById('btn-toggle-camera');
    const video = document.getElementById('qr-video');
    const status = document.getElementById('camera-status');

    if (videoStream) {
        stopCamera();
        container.style.display = 'none';
        toggleBtn.textContent = 'Bật Camera quét mã ngay';
        return;
    }

    container.style.display = 'block';
    toggleBtn.textContent = 'Tắt Camera';
    status.textContent = 'Đang khởi động camera...';

    try {
        videoStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        });
        video.srcObject = videoStream;
        await video.play();
        status.textContent = 'Đang quét... Hãy hướng camera về phía mã QR trên màn chiếu.';

        if ('BarcodeDetector' in window) {
            const barcodeDetector = new BarcodeDetector({ formats: ['qr_code'] });
            scanningInterval = setInterval(async () => {
                try {
                    const barcodes = await barcodeDetector.detect(video);
                    if (barcodes && barcodes.length > 0) {
                        const rawValue = barcodes[0].rawValue;
                        handleScannedText(rawValue);
                    }
                } catch (e) {}
            }, 300);
        } else {
            status.textContent = 'Thiết bị chưa hỗ trợ quét trực tiếp trong trình duyệt. Hãy dùng camera điện thoại quét mã QR chiếu trên màn hình.';
        }
    } catch (err) {
        status.textContent = 'Không thể truy cập camera. Hãy dùng camera điện thoại quét mã QR hoặc dán mã thủ công.';
    }
}

function stopCamera() {
    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        videoStream = null;
    }
    if (scanningInterval) {
        clearInterval(scanningInterval);
        scanningInterval = null;
    }
}

function handleScannedText(text) {
    stopCamera();
    document.getElementById('camera-container').style.display = 'none';
    
    let code = text;
    try {
        const url = new URL(text);
        if (url.searchParams.get('code')) {
            code = url.searchParams.get('code');
        } else if (url.hash) {
            const hp = new URLSearchParams(url.hash.slice(1));
            if (hp.get('code')) code = hp.get('code');
        }
    } catch (e) {}

    applyCode(code);
}

checkUrlCode();
</script>
@endsection
