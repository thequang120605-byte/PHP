@extends('layout')

@section('title', 'Tạo tài khoản')

@section('content')
<section class="panel auth" style="max-width: 540px;">
    <div class="auth-brand">
        <img src="{{ asset('images/logo-huit.png') }}" alt="Logo HUIT">
        <div class="auth-brand-text">
            <span class="auth-brand-sub">ĐẠI HỌC CÔNG THƯƠNG TP.HCM</span>
            <span class="auth-brand-title">HUIT EventPass</span>
        </div>
    </div>

    <small class="eyebrow">SẴN SÀNG TRẢI NGHIỆM</small>
    <h1>Tạo tài khoản sinh viên</h1>
    <p class="muted">Đăng ký tài khoản để khám phá hoạt động và quản lý điểm rèn luyện.</p>

    <form method="POST" action="/register">
        @csrf
        
        <label>
            Họ và tên
            <input type="text" name="name" value="{{ old('name') }}" placeholder="VD: Nguyễn Văn An" required autofocus>
        </label>

        <div class="two">
            <label>
                Mã số sinh viên (MSSV)
                <input type="text" name="student_code" value="{{ old('student_code') }}" placeholder="8 - 15 chữ số" required pattern="[0-9]{8,15}" title="MSSV từ 8 đến 15 chữ số">
            </label>

            <label>
                Lớp sinh hoạt
                <input type="text" name="class_name" value="{{ old('class_name') }}" placeholder="VD: 12DHTH01" required>
            </label>
        </div>

        <label>
            Địa chỉ Email
            <input type="email" name="email" value="{{ old('email') }}" placeholder="VD: sv_2001210001@huit.edu.vn" required autocomplete="username">
        </label>

        <div class="two">
            <label>
                Mật khẩu
                <input type="password" name="password" placeholder="Tối thiểu 10 ký tự (chữ & số)" minlength="10" required autocomplete="new-password">
            </label>

            <label>
                Nhập lại mật khẩu
                <input type="password" name="password_confirmation" placeholder="Xác nhận mật khẩu" required autocomplete="new-password">
            </label>
        </div>

        <button type="submit" class="btn full">Tạo tài khoản</button>
    </form>

    <p><a href="/login">Đã có tài khoản? Đăng nhập</a></p>
</section>
@endsection
