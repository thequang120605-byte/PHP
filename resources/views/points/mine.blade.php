@extends('layout')

@section('title', 'Bảng điểm rèn luyện của tôi')

@section('content')
<div class="page-heading">
    <small class="eyebrow">KẾT QUẢ RÈN LUYỆN SINH VIÊN</small>
    <h1>Bảng điểm rèn luyện hoạt động phong trào</h1>
    <p>Tổng hợp điểm tích lũy từ các hoạt động, sự kiện và chuyên đề đã được xác nhận tham dự.</p>
</div>

<div class="filters">
    <form method="GET" action="/my-points" style="display: flex; gap: 10px; align-items: center; width: 100%;">
        <label style="margin: 0; min-width: 250px;">
            <select name="semester_id" onchange="this.form.submit()">
                <option value="">-- Tất cả học kỳ --</option>
                @foreach($semesters as $s)
                    <option value="{{ $s->id }}" @selected($semesterId == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn secondary">Lọc học kỳ</button>
    </form>
</div>

@forelse($transcript as $data)
    <div class="panel" style="margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; color: #1e3a8a;">{{ $data['semester']->name }}</h2>
                <small class="muted">Mã học kỳ: {{ $data['semester']->code }} ({{ $data['semester']->starts_at->format('d/m/Y') }} – {{ $data['semester']->ends_at->format('d/m/Y') }})</small>
            </div>
            <div style="text-align: right;">
                <span class="muted" style="font-size: 11px;">TỔNG ĐIỂM HOẠT ĐỘNG CLB/KHOA</span>
                <h1 style="margin: 0; color: #15803d; font-size: 32px;">{{ $data['total_points'] }} <span style="font-size: 16px;">điểm</span></h1>
            </div>
        </div>

        @forelse($data['by_criteria'] as $critId => $critGroup)
            <div style="margin-bottom: 20px; background: #f8fafc; border-radius: 8px; padding: 16px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div>
                        <strong style="color: #334155; font-size: 15px;">
                            [{{ $critGroup['criteria']->code }}] {{ $critGroup['criteria']->name }}
                        </strong>
                        <small class="muted" style="display: block;">Quy định trần: tối đa {{ $critGroup['criteria']->max_points }} điểm / học kỳ</small>
                    </div>
                    <div>
                        <span class="badge info" style="font-size: 13px;">
                            Đạt: <strong>{{ $critGroup['total'] }}</strong> / {{ $critGroup['criteria']->max_points }} đ
                        </span>
                    </div>
                </div>

                <div class="table-wrap">
                    <table style="background: white; border-radius: 6px; overflow: hidden;">
                        <thead>
                            <tr>
                                <th>Tên hoạt động / Sự kiện</th>
                                <th>Thời gian diễn ra</th>
                                <th>Điểm ghi nhận</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú / Điều chỉnh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($critGroup['items'] as $item)
                                <tr>
                                    <td>
                                        <a href="/events/{{ $item->event_id }}"><strong>{{ $item->event->title }}</strong></a>
                                    </td>
                                    <td>{{ $item->event->starts_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <strong style="color: {{ $item->status === 'approved' ? '#15803d' : '#b91c1c' }}; font-size: 14px;">
                                            +{{ $item->points }} đ
                                        </strong>
                                    </td>
                                    <td>
                                        @if($item->status === 'approved')
                                            <span class="badge green">Đã duyệt</span>
                                        @else
                                            <span class="badge danger">Đã thu hồi</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->notes)
                                            <small style="color: #b45309;">{{ $item->notes }}</small>
                                        @else
                                            <small class="muted">Hợp lệ</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="muted" style="text-align: center; padding: 20px;">Chưa có hoạt động nào được ghi nhận điểm trong học kỳ này.</p>
        @endforelse
    </div>
@empty
    <div class="panel" style="text-align: center; padding: 40px;">
        <p class="muted">Chưa có dữ liệu học kỳ nào.</p>
    </div>
@endforelse

<div class="notice">
    <strong>Lưu ý quan trọng:</strong> Điểm rèn luyện từ các hoạt động Đoàn – Hội, CLB là dữ liệu đề xuất căn cứ. Bảng điểm tổng thể học kỳ cuối cùng cần dữ liệu và quyết định xác nhận từ Phòng Công tác Sinh viên (hoặc đơn vị có thẩm quyền theo quy chế trường).
</div>
@endsection
