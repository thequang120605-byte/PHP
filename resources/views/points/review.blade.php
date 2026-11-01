@extends('layout')

@section('title', 'Duyệt điểm rèn luyện')

@section('content')
<a href="/admin/events/{{ $event->id }}/participants">← Trở về danh sách sự kiện</a>

<div class="heading">
    <div>
        <small class="eyebrow">MODULE ĐIỂM RÈN LUYỆN</small>
        <h1>Duyệt điểm rèn luyện sự kiện</h1>
        <p>
            <strong>{{ $event->title }}</strong> · 
            Học kỳ: <strong>{{ $event->semester?->name ?? 'Chưa gán' }}</strong> · 
            Tiêu chí: <strong>{{ $event->criteria?->code }} (Trần: {{ $event->criteria?->max_points }} đ)</strong> · 
            Đề xuất: <strong>+{{ $event->proposed_points }} đ/sinh viên</strong>
        </p>
    </div>
    @if($event->semester_id && $event->criteria_id && $eligibleRegistrations->count() > 0)
        <form method="POST" action="/events/{{ $event->id }}/points/approve">
            @csrf
            <button class="btn green" style="background: #15803d; color: white;" onclick="return confirm('Duyệt tối đa 200 hồ sơ chưa ghi nhận tiếp theo?');">
                Duyệt đợt tiếp theo (tối đa 200 SV)
            </button>
        </form>
    @endif
</div>

@if(!$event->semester_id || !$event->criteria_id)
    <div class="alert error">
        <strong>Chưa thể duyệt điểm!</strong> Sự kiện này chưa được gắn Học kỳ hoặc Tiêu chí rèn luyện. Vui lòng vào trang chỉnh sửa sự kiện để cấu hình trước.
    </div>
@endif

<div class="notice">
    <strong>Cơ chế bảo vệ dữ liệu nghiệp vụ:</strong>
    <p>1. <strong>Chống cộng trùng (Idempotency):</strong> Dù duyệt lại nhiều lần hoặc chạy lại tác vụ, hệ thống chỉ giữ 1 bản ghi duy nhất theo bộ <code>(sinh viên, sự kiện)</code>.<br>
    2. <strong>Kiểm soát mức trần (Point Cap):</strong> Điểm thực nhận sẽ tự động hạ xuống nếu tổng điểm trong tiêu chí vượt quá mức trần quy định ({{ $event->criteria?->max_points ?? 20 }} điểm).<br>
    3. <strong>Lịch sử điều chỉnh:</strong> Mọi thao tác tăng, giảm hoặc thu hồi đều bắt buộc nhập lý do và lưu vết kiểm toán.</p>
</div>

<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>MSSV</th>
                <th>Họ và tên</th>
                <th>Lớp</th>
                <th>Trạng thái điểm danh</th>
                <th>Ghi nhận hoàn thành</th>
                <th>Điểm rèn luyện</th>
                <th>Trạng thái ghi nhận</th>
                <th>Thao tác điều chỉnh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($eligibleRegistrations as $reg)
                @php
                    $pt = $recordedPoints->get($reg->user_id);
                @endphp
                <tr>
                    <td class="mono"><strong>{{ $reg->user->student_code }}</strong></td>
                    <td>{{ $reg->user->name }}</td>
                    <td>{{ $reg->user->class_name }}</td>
                    <td><span class="badge green">Hoàn thành</span></td>
                    <td>
                        <small>{{ $reg->completion?->completed_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }}</small>
                        <small>{{ $reg->completion?->method === 'paper_review' ? 'Đối soát giấy' : 'QR cuối sự kiện' }}</small>
                    </td>
                    <td>
                        @if($pt)
                            <strong style="color: {{ $pt->status === 'approved' ? '#15803d' : '#b91c1c' }}; font-size: 15px;">
                                {{ $pt->points }} đ
                            </strong>
                            @if($pt->notes)
                                <small style="display:block; color: #b45309;">{{ $pt->notes }}</small>
                            @endif
                        @else
                            <span class="muted">+{{ $event->proposed_points }} đ (Đề xuất)</span>
                        @endif
                    </td>
                    <td>
                        @if($pt && $pt->status === 'approved')
                            <span class="badge green">Đã duyệt ({{ $pt->approved_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y') }})</span>
                        @elseif($pt && $pt->status === 'revoked')
                            <span class="badge danger">Đã thu hồi</span>
                        @else
                            <span class="badge warning">Chờ duyệt</span>
                        @endif
                    </td>
                    <td>
                        @if($pt)
                            <details>
                                <summary style="cursor: pointer; color: #2563eb; font-size: 12px; font-weight: 600;">Điều chỉnh điểm</summary>
                                <form method="POST" action="/points/{{ $pt->id }}/adjust" style="margin-top: 10px; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    @csrf
                                    <label style="font-size: 11px; margin-bottom: 6px;">Điểm mới:
                                        <input type="number" name="new_points" value="{{ $pt->points }}" min="0" max="{{ $event->criteria?->max_points ?? 100 }}" required style="padding: 4px 8px; font-size: 12px;">
                                    </label>
                                    <label style="font-size: 11px; margin-bottom: 6px;">Lý do điều chỉnh (bắt buộc):
                                        <input type="text" name="reason" placeholder="VD: Sai sót nhập liệu / Vi phạm nội quy" required style="padding: 4px 8px; font-size: 12px;">
                                    </label>
                                    <button class="btn btn-sm" style="margin-top: 6px;">Cập nhật điểm</button>
                                </form>
                            </details>
                        @else
                            <form method="POST" action="/events/{{ $event->id }}/points/approve" style="display: inline-block;">
                                @csrf
                                <input type="hidden" name="registration_ids[]" value="{{ $reg->id }}">
                                <button class="btn-sm btn secondary">Duyệt riêng</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px;" class="muted">
                        Chưa có sinh viên nào đủ điều kiện hoàn thành để duyệt điểm.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pagination">{{ $eligibleRegistrations->links('pagination::simple-default') }}</div><p class="fine">Mỗi lần duyệt tối đa 200 hồ sơ. Điểm là kết quả đề xuất trong hệ thống đồ án, không tự đồng bộ cổng trường.</p>
@endsection
