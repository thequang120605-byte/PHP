@extends('layout')

@section('title', 'Đối soát giấy · ' . $event->title)

@section('content')
<div style="margin-bottom: 16px;">
    <a href="/events/{{ $event->id }}" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #2563eb;">
        ← Quay lại trang sự kiện
    </a>
</div>

<div class="page-heading">
    <small class="eyebrow">DỰ PHÒNG & ĐỐI SOÁT NGOẠI LỆ</small>
    <h1>Đối soát điểm danh giấy cuối sự kiện</h1>
    <p class="muted">{{ $event->title }} · {{ $event->venue }}</p>
</div>

<div class="detail">
    <section class="panel">
        <h2 style="font-size: 18px; margin-bottom: 8px;">Nhập danh sách giấy có chữ ký</h2>
        <p class="notice" style="margin-bottom: 16px;">
            Dùng trong tình huống sinh viên gặp sự cố thiết bị/camera hoặc mất kết nối tại hội trường. Danh sách bắt buộc có chữ ký và bản chụp/scan đính kèm để làm bằng chứng pháp lý.
        </p>

        <form method="POST" enctype="multipart/form-data" action="/events/{{ $event->id }}/attendance/paper">
            @csrf
            <label>
                Danh sách sinh viên (mỗi dòng: <code>MSSV | Họ tên | Lớp | Có mặt (1 hoặc 0)</code>):
                <textarea name="raw_text" rows="7" required placeholder="2001234567 | Nguyễn Văn A | 14DHTH01 | 1" style="font-family: monospace; font-size: 13px;">{{ old('raw_text') }}</textarea>
            </label>

            <label>
                Bản chụp / scan danh sách giấy có chữ ký (PDF, JPG, PNG, WEBP tối đa 10 MB):
                <input type="file" name="scan_file" required accept=".pdf,.jpg,.jpeg,.png,.webp">
            </label>

            <label>
                Ghi chú và căn cứ xác minh đối soát (bắt buộc):
                <textarea name="notes" rows="3" required maxlength="1000" placeholder="Ví dụ: Đối soát bổ sung cho sinh viên bị hỏng camera điện thoại cuối buổi, đã đối chiếu thẻ sinh viên">{{ old('notes') }}</textarea>
            </label>

            <button class="btn" style="padding: 12px 20px;">
                Xác nhận lưu đối soát giấy & Bằng chứng
            </button>
        </form>
    </section>

    <aside>
        <section class="panel spaced">
            <h2 style="font-size: 17px; margin-bottom: 14px;">Lịch sử các đợt đối soát giấy</h2>
            @forelse($paperSheets as $sheet)
                <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 12px;">
                    <strong style="display: block; color: #0f172a; font-size: 14px;">{{ $sheet->total_records }} sinh viên được ghi nhận</strong>
                    <div style="font-size: 12px; color: #64748b; margin: 4px 0;">
                        Thực hiện bởi: <strong>{{ $sheet->user->name }}</strong> · {{ $sheet->created_at->timezone('Asia/Ho_Chi_Minh')->format('H:i · d/m/Y') }}
                    </div>
                    <a href="/attendance/paper/{{ $sheet->id }}/file" class="btn btn-sm secondary" style="margin-top: 6px;">
                        📄 Tải bản scan đối soát (Bảo mật)
                    </a>
                </div>
            @empty
                <p class="muted" style="text-align: center; padding: 20px 0;">Chưa có đợt đối soát giấy nào được nhập cho sự kiện này.</p>
            @endforelse
        </section>
    </aside>
</div>
@endsection
