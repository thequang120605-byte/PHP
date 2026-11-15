@extends('layout')

@section('title', '429 · Quá nhiều yêu cầu')

@section('content')
<div style="max-width: 580px; margin: 40px auto;">
    <section class="panel auth" style="text-align: center; padding: 36px 28px;">
        <div style="font-size: 48px; margin-bottom: 12px;">⚡</div>
        <h1 style="font-size: 24px; margin-bottom: 8px; color: #0f172a;">429 · Quá nhiều yêu cầu gửi tới máy chủ</h1>
        <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 24px;">
            Bạn đã gửi yêu cầu quá nhanh trong một khoảng thời gian ngắn (Rate Limit kích hoạt để bảo vệ máy chủ khỏi nguy cơ spam/tấn công). Vui lòng chờ khoảng 1 phút rồi thử lại.
        </p>

        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <button type="button" class="btn" onclick="setTimeout(() => window.location.reload(), 1000)">Thử lại ngay</button>
            <a class="btn secondary" href="/">Về trang chủ</a>
        </div>
    </section>
</div>
@endsection
