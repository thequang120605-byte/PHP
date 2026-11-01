@extends('layout')

@section('title', 'Gửi phản hồi sai sót')

@section('content')
<a href="/events/{{ $event->id }}">← Trở về sự kiện</a>

<div class="page-heading">
    <small class="eyebrow">ĐỐI SOÁT & GIẢI QUYẾT SAI SÓT</small>
    <h1>Gửi phản hồi / Khiếu nại thông tin sự kiện</h1>
    <p><strong>{{ $event->title }}</strong> · Thời hạn tiếp nhận: {{ $event->dispute_closes_at ? $event->dispute_closes_at->format('d/m/Y H:i') : 'Trong vòng 7 ngày sau kết thúc' }}</p>
</div>

<div class="detail">
    <section class="panel">
        <form method="POST" enctype="multipart/form-data" action="/events/{{ $event->id }}/disputes">
            @csrf

            <label>Loại vấn đề gặp phải:
                <select name="type" required>
                    <option value="missing_attendance">Đăng ký thành công nhưng thiếu tên trong danh sách điểm danh</option>
                    <option value="missing_checkout">Có tham dự thực tế nhưng thiếu giờ ra (Check-out)</option>
                    <option value="missing_points">Đủ điều kiện hoàn thành nhưng chưa được ghi nhận điểm</option>
                    <option value="incorrect_points">Điểm rèn luyện bị ghi nhận sai lệch</option>
                </select>
            </label>

            <label>Mô tả chi tiết sự việc:
                <textarea name="description" rows="5" placeholder="VD: Tôi có tham dự tại hội trường B từ 14h đến 16h30, lúc về máy quét QR bị nghẽn nên chưa kịp quét ra. Tôi xin đính kèm ảnh chụp tại sự kiện làm minh chứng..." required></textarea>
            </label>

            <label>Đính kèm minh chứng (Ảnh chụp tại sự kiện, bản scan thẻ SV, phiếu giữ chỗ) - JPG, PNG, PDF tối đa 10 MB:
                <input type="file" name="evidence" accept="image/*,application/pdf">
            </label>

            <div style="margin-top: 20px;">
                <button class="btn">Gửi yêu cầu xác minh</button>
            </div>
        </form>
    </section>

    <aside class="panel">
        <h3>Quy trình tiếp nhận và đối soát</h3>
        <ol style="font-size: 13px; color: #475569; padding-left: 20px; line-height: 1.8;">
            <li><strong>Chờ xử lý (Pending):</strong> Hệ thống tiếp nhận mã yêu cầu và chuyển đến Ban tổ chức sự kiện.</li>
            <li><strong>Đang xác minh (In review):</strong> BTC đối chiếu nhật ký quét QR, bảng ký tên giấy dự phòng và ảnh minh chứng.</li>
            <li><strong>Chấp nhận / Từ chối (Resolved / Rejected):</strong> Cập nhật trạng thái tham dự hoặc điều chỉnh điểm kèm thông báo lý do rõ ràng.</li>
        </ol>
        <p class="fine">Vui lòng cung cấp thông tin trung thực để đảm bảo tính công bằng trong xét duyệt rèn luyện.</p>
    </aside>
</div>
@endsection
