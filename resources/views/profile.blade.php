@extends('layout')

@section('title', 'Hồ sơ cá nhân')

@section('content')
<div class="page-heading">
    <small class="eyebrow">TÀI KHOẢN CÁ NHÂN</small>
    <h1>Hồ sơ cá nhân</h1>
    <p class="muted">Thay đổi hồ sơ tại đây. Thông tin gốc trên các phiếu đăng ký đã lưu sẽ được giữ nguyên để đối chiếu.</p>
</div>

<section class="panel narrow">
    <form method="POST" action="/profile">
        @csrf
        @method('PUT')

        <label>
            Họ và tên
            <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required>
        </label>

        <div class="two">
            <label>
                Mã số sinh viên (MSSV)
                <input type="text" name="student_code" value="{{ old('student_code', auth()->user()->student_code) }}" required pattern="[0-9]{8,15}">
            </label>

            <label>
                Lớp sinh hoạt
                <input type="text" name="class_name" value="{{ old('class_name', auth()->user()->class_name) }}" required>
            </label>
        </div>

        <label>
            Địa chỉ Email (Cố định theo tài khoản)
            <input type="email" value="{{ auth()->user()->email }}" disabled>
        </label>

        <p class="fine">Mẹo: Đảm bảo thông tin MSSV và Lớp chính xác để BTC công nhận điểm rèn luyện.</p>

        <div style="margin-top: 10px;">
            <button type="submit" class="btn">Lưu thay đổi hồ sơ</button>
        </div>
    </form>
</section>
@endsection
