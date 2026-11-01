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

        <!-- Trạng thái nhận diện mã -->
        <div id="detected-banner" style="display: none; background: #ecfdf5; border: 1.5px solid #6ee7b7; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 22px;">✅</span>
                <div>
                    <strong style="color: #065f46; display: block; font-size: 14px;">Đã nhận diện mã điểm danh hợp lệ!</strong>
                    <span style="color: #047857; font-size: 12.5px;">Mã xác thực từ máy chiếu đã được nạp sẵn. Hãy bấm nút xác nhận bên dưới.</span>
                </div>
            </div>
        </div>

        <!-- Form xác nhận chính -->
        <form id="attendance-form" method="POST" action="/events/{{ $event->id }}/attendance/complete">
            @csrf
            
            <!-- Hidden input lưu mã QR thật -->
            <input type="hidden" id="code" name="code" value="{{ old('code', $code) }}">

            <!-- Các phương án lấy mã điểm danh khi chưa có mã -->
            <div id="no-code-options" style="display: {{ empty($code) ? 'block' : 'none' }}; margin-bottom: 20px;">
                <p style="font-size: 13.5px; color: #475569; margin-bottom: 16px; line-height: 1.6;">
                    Quy trình chuẩn: Cuối sự kiện, BTC sẽ chiếu <strong>mã QR động</strong> lên máy chiếu hội trường. Bạn chọn một trong các cách sau để xác nhận:
                </p>

                <!-- Phương án A: Dành cho Test/Demo trên máy tính (Cực tiện!) -->
                @if(!empty($demoCode))
                    <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1.5px solid #93c5fd; border-radius: 14px; padding: 16px; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 700; color: #1e40af; font-size: 13.5px; display: flex; align-items: center; gap: 6px;">
                                <span style="display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 8px #10b981;"></span>
                                Đợt điểm danh đang phát tại hội trường!
                            </span>
                            <span class="badge info">Demo / Thử nghiệm</span>
                        </div>
                        <p style="font-size: 12.5px; color: #3b82f6; margin-bottom: 12px;">
                            Phát hiện đang có đợt điểm danh hoạt động. Bạn có thể nạp mã ngay mà không cần điện thoại quét màn hình:
                        </p>
                        <button type="button" class="btn" style="width: 100%; background: #2563eb;" onclick="loadDemoCode('{{ $demoCode }}')">
                            ⚡ Nạp mã tự động từ hội trường (1-Click Test)
                        </button>
                    </div>
                @else
                    <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 14px; padding: 14px 16px; margin-bottom: 16px;">
                        <strong style="color: #b45309; display: block; font-size: 13.5px; margin-bottom: 4px;">
                            ⏳ Chưa có đợt điểm danh đang mở tại sự kiện này
                        </strong>
                        <span style="color: #92400e; font-size: 12.5px; line-height: 1.5; display: block;">
                            BTC sự kiện sẽ mở mã QR chung trong vòng 10 phút sau khi sự kiện kết thúc.
                        </span>
                        @if(auth()->user()?->role === 'admin' || (auth()->user() && $event->hasStaffRole(auth()->user(), ['scanner', 'manager'])))
                            <div style="margin-top: 10px;">
                                <a class="btn secondary btn-sm" href="/events/{{ $event->id }}/attendance/scan" target="_blank">
                                    👉 Mở trang phát mã QR (Dành cho Quản trị viên/BTC) ↗
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Phương án B: Bật Camera quét trực tiếp trên Web -->
                <div style="border: 1.5px dashed #cbd5e1; border-radius: 14px; padding: 18px; text-align: center; background: #ffffff; margin-bottom: 16px;">
                    <strong style="display: block; font-size: 14px; color: #1e293b; margin-bottom: 6px;">
                        📷 Quét mã QR bằng Camera thiết bị
                    </strong>
                    <p style="font-size: 12.5px; color: #64748b; margin-bottom: 12px;">
                        Hướng camera của máy tính / điện thoại về phía mã QR đang chiếu trên màn hình.
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

                <!-- Phương án C: Nhập tay dự phòng (Collapsible) -->
                <details style="font-size: 13px; color: #64748b; text-align: left;">
                    <summary style="cursor: pointer; font-weight: 600; color: #2563eb; margin-bottom: 8px;">
                        Nhập hoặc dán mã thủ công (dự phòng)
                    </summary>
                    <div style="padding-top: 8px;">
                        <input type="text" id="manual-code-input" placeholder="Dán mã điểm danh (payload) vào đây..." style="font-size: 13px; font-family: monospace;" oninput="onManualInput(this.value)">
                    </div>
                </details>
            </div>

            <!-- Nút gửi xác nhận -->
            <button type="submit" id="btn-submit-attendance" class="btn full" style="font-size: 15px; padding: 14px; margin-top: 10px;" {{ empty($code) ? 'disabled' : '' }}>
                Xác nhận tôi có mặt cuối sự kiện
            </button>
        </form>

        <p class="fine" style="margin-top: 20px; line-height: 1.5;">
            Lưu ý: Hệ thống chỉ xác nhận cho tài khoản đang đăng nhập. Không chia sẻ mã cho sinh viên vắng mặt. Điểm đề xuất sẽ được người có quyền duyệt chính thức.
        </p>

        <div style="text-align: center; margin-top: 16px;">
            <a href="/my-registrations" style="font-size: 13px; font-weight: 600; color: #64748b;">
                Xem danh sách đăng ký của tôi →
            </a>
        </div>
    </section>
</div>

<script>
// 1. Kiểm tra mã từ URL hash hoặc URL query params
function checkUrlCode() {
    let code = '';
    // Thử lấy từ Query parameter ?code=...
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('code')) {
        code = urlParams.get('code');
    }
    // Thử lấy từ URL Hash #code=...
    if (!code && window.location.hash) {
        const hashParams = new URLSearchParams(window.location.hash.slice(1));
        if (hashParams.get('code')) {
            code = hashParams.get('code');
        }
    }
    
    if (code) {
        applyCode(code);
        // Xóa mã trên URL để bảo mật sau khi nhận diện
        history.replaceState(null, '', window.location.pathname);
    }
}

// 2. Điền mã và cập nhật giao diện
function applyCode(code) {
    if (!code) return;
    document.getElementById('code').value = code;
    document.getElementById('detected-banner').style.display = 'block';
    document.getElementById('no-code-options').style.display = 'none';
    const submitBtn = document.getElementById('btn-submit-attendance');
    submitBtn.removeAttribute('disabled');
    submitBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
    submitBtn.innerHTML = '✓ Đã sẵn sàng · Bấm để xác nhận ngay';
}

// 3. Nạp mã Demo nhanh
function loadDemoCode(demoCode) {
    applyCode(demoCode);
}

// 4. Nhập tay thủ công
function onManualInput(val) {
    if (val.trim()) {
        document.getElementById('code').value = val.trim();
        document.getElementById('btn-submit-attendance').removeAttribute('disabled');
    }
}

// 5. Trình quét Camera Scanner HTML5 (BarcodeDetector native API)
let videoStream = null;
let scanningInterval = null;

async function toggleCameraScanner() {
    const container = document.getElementById('camera-container');
    const toggleBtn = document.getElementById('btn-toggle-camera');
    const video = document.getElementById('qr-video');
    const status = document.getElementById('camera-status');

    if (videoStream) {
        // Tắt camera
        stopCamera();
        container.style.display = 'none';
        toggleBtn.textContent = 'Bật Camera quét mã ngay';
        return;
    }

    // Bật camera
    container.style.display = 'block';
    toggleBtn.textContent = 'Tắt Camera';
    status.textContent = 'Đang khởi động camera...';

    try {
        videoStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        });
        video.srcObject = videoStream;
        await video.play();
        status.textContent = 'Đang quét... Hãy đưa mã QR trên màn hình vào giữa khung hình.';

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
            status.textContent = 'Trình duyệt chưa hỗ trợ quét trực tiếp qua WebCam. Hãy dùng Camera điện thoại quét mã QR hoặc bấm nút Nạp mã bên trên.';
        }
    } catch (err) {
        status.textContent = 'Không thể truy cập camera (yêu cầu quyền hoặc thiết bị không có camera). Hãy dùng nút Nạp mã Demo hoặc camera điện thoại.';
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
    
    // Nếu text là URL chứa code
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

// Khởi chạy khi load trang
checkUrlCode();
</script>
@endsection
