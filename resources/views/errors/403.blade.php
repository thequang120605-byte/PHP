@extends('layout')

@section('title', '403 · Không có quyền truy cập')

@section('content')
<div style="max-width: 580px; margin: 40px auto;">
    <section class="panel auth" style="text-align: center; padding: 36px 28px;">
        <div style="font-size: 48px; margin-bottom: 12px;">🔒</div>
        <h1 style="font-size: 24px; margin-bottom: 8px; color: #0f172a;">403 · Không có quyền truy cập</h1>
        <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 24px;">
            Tài khoản hiện tại của bạn không có quyền thực hiện thao tác hoặc truy cập vào tài nguyên này. Bạn chỉ có thể thao tác với dữ liệu thuộc quyền hoặc sự kiện được phân công.
        </p>

        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a class="btn" href="/">Khám phá sự kiện</a>
            @auth
                @if(auth()->user()->role === 'admin')
                    <a class="btn secondary" href="/admin">Trung tâm Quản trị</a>
                @else
                    <a class="btn secondary" href="/my-registrations">Đăng ký của tôi</a>
                @endif
            @endauth
        </div>

        <p class="fine" style="margin-top: 24px;">
            Nếu bạn được phân công phụ trách sự kiện (Scanner, Approver, Manager), vui lòng liên hệ Ban Quản trị để kiểm tra phân quyền.
        </p>
    </section>
</div>
@endsection
