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
                Mã số sinh viên (Cố định)
                <input type="text" value="{{ auth()->user()->student_code ?? 'N/A' }}" disabled style="background: #f1f5f9; color: #64748b; cursor: not-allowed;">
            </label>

            <label>
                Lớp sinh hoạt
                <input type="text" name="class_name" value="{{ old('class_name', auth()->user()->class_name) }}" required>
            </label>
        </div>

        <label>
            Địa chỉ Email (Cố định theo tài khoản)
            <input type="email" value="{{ auth()->user()->email }}" disabled style="background: #f1f5f9; color: #64748b; cursor: not-allowed;">
        </label>

        <p class="fine">Mẹo: MSSV và Email được khóa cố định để đảm bảo tính toàn vẹn danh tính khi cấp điểm rèn luyện. Nếu cần đính chính, vui lòng liên hệ Ban Quản trị.</p>

        <div style="margin-top: 10px;">
            <button type="submit" class="btn">Lưu thay đổi hồ sơ</button>
        </div>
    </form>
</section>
@endsection
