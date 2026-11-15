@extends('layout')

@section('title', '404 · Không tìm thấy trang')

@section('content')
<div style="max-width: 580px; margin: 40px auto;">
    <section class="panel auth" style="text-align: center; padding: 36px 28px;">
        <div style="font-size: 48px; margin-bottom: 12px;">🔍</div>
        <h1 style="font-size: 24px; margin-bottom: 8px; color: #0f172a;">404 · Không tìm thấy nội dung</h1>
        <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 24px;">
            Nội dung bạn đang tìm kiếm không tồn tại, đã bị xóa hoặc chưa được công bố trên hệ thống HUIT EventPass.
        </p>

        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a class="btn" href="/">Xem danh sách sự kiện</a>
            <button type="button" class="btn secondary" onclick="history.back()">Quay lại trang trước</button>
        </div>
    </section>
</div>
@endsection
