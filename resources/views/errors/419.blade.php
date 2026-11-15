@extends('layout')

@section('title', '419 · Phiên làm việc hết hạn')

@section('content')
<div style="max-width: 580px; margin: 40px auto;">
    <section class="panel auth" style="text-align: center; padding: 36px 28px;">
        <div style="font-size: 48px; margin-bottom: 12px;">⏳</div>
        <h1 style="font-size: 24px; margin-bottom: 8px; color: #0f172a;">419 · Phiên làm việc đã hết hạn</h1>
        <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 24px;">
            Phiên bảo mật (CSRF Token) đã hết hạn do trang web được mở quá lâu mà không có thao tác. Vui lòng làm mới trang bằng phương thức GET hoặc đăng nhập lại trước khi gửi biểu mẫu.
        </p>

        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <button type="button" class="btn" onclick="window.location.reload()">Tải lại trang</button>
            <a class="btn secondary" href="/">Về trang chủ</a>
        </div>
    </section>
</div>
@endsection
