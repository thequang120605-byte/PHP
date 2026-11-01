@extends('layout')

@section('title', 'Đăng nhập')

@section('content')
<section class="panel auth">
    <div class="auth-brand">
        <img src="{{ asset('images/logo-huit.png') }}" alt="Logo HUIT">
        <div class="auth-brand-text">
            <span class="auth-brand-sub">ĐẠI HỌC CÔNG THƯƠNG TP.HCM</span>
            <span class="auth-brand-title">HUIT EventPass</span>
        </div>
    </div>

    <small class="eyebrow">CHÀO MỪNG TRỞ LẠI</small>
    <h1>Đăng nhập</h1>
    <p class="muted">Theo dõi hoạt động và lịch sử đăng ký của bạn.</p>

    <form method="POST" action="/login">
        @csrf
        <label>
            Email sinh viên / quản trị
            <input type="email" name="email" value="{{ old('email') }}" placeholder="VD: sv_2001210001@huit.edu.vn" required autocomplete="username" autofocus>
        </label>

        <label>
            Mật khẩu
            <input type="password" name="password" placeholder="Nhập mật khẩu..." required autocomplete="current-password">
        </label>

        <button type="submit" class="btn full">Đăng nhập</button>
    </form>

    <p>Chưa có tài khoản? <a href="/register">Đăng ký sinh viên ngay</a></p>
</section>
@endsection
