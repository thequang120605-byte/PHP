@extends('layout')

@section('title', 'Báo cáo thống kê tổng thể')

@section('content')
<a href="/admin">← Trở về bảng quản trị</a>

<div class="heading">
    <div>
        <small class="eyebrow">BÁO CÁO VÀ PHÂN TÍCH DỮ LIỆU</small>
        <h1>Báo cáo Đăng ký, Tham dự & Điểm rèn luyện</h1>
        <p>Tổng hợp hiệu quả tổ chức hoạt động, tỷ lệ tham gia thực tế và kết quả xét duyệt điểm theo học kỳ.</p>
    </div>
</div>

<div class="filters">
    <form method="GET" action="/admin/reports" style="display: flex; gap: 10px; align-items: center; width: 100%;">
        <label style="margin: 0; min-width: 280px;">
            <select name="semester_id" onchange="this.form.submit()">
                @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" @selected($selectedSemester?->id == $sem->id)>
                        {{ $sem->name }} ({{ $sem->code }})
                    </option>
                @endforeach
            </select>
        </label>
        <button class="btn secondary">Xem báo cáo học kỳ</button>
    </form>
</div>

@if($semesterReport)
    <div class="stat-grid-4">
        <div class="panel" style="padding: 16px;">
            <small class="muted">Số sự kiện tổ chức</small>
            <h2 style="margin: 5px 0; color: #1e3a8a;">{{ $semesterReport['events_count'] }}</h2>
        </div>
        <div class="panel" style="padding: 16px;">
            <small class="muted">Sinh viên nhận điểm</small>
            <h2 style="margin: 5px 0; color: #0284c7;">{{ $semesterReport['total_students'] }}</h2>
        </div>
        <div class="panel" style="padding: 16px;">
            <small class="muted">Tổng điểm đã cấp</small>
            <h2 style="margin: 5px 0; color: #15803d;">{{ $semesterReport['total_points'] }} <span style="font-size: 14px;">đ</span></h2>
        </div>
        <div class="panel" style="padding: 16px;">
            <small class="muted">Tình trạng học kỳ</small>
            <h2 style="margin: 5px 0; color: #7c3aed;">
                {{ $selectedSemester->is_active ? 'Đang mở' : 'Đã đóng' }}
            </h2>
        </div>
    </div>
@endif

<div class="panel table-wrap">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="margin: 0;">Hiệu quả tổ chức theo từng sự kiện</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tên sự kiện</th>
                <th>Tiêu chí</th>
                <th>Sức chứa</th>
                <th>Đăng ký (Waitlist)</th>
                <th>Đủ điều kiện</th>
                <th>Thiếu giờ ra</th>
                <th>Vắng mặt</th>
                <th>Chưa ghi nhận</th>
                <th>Tỷ lệ tham dự</th>
                <th>Điểm cấp</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($eventReports as $item)
                @php $ev = $item['event']; @endphp
                <tr>
                    <td>
                        <a href="/events/{{ $ev->id }}"><strong>{{ $ev->title }}</strong></a>
                        <small class="muted" style="display: block;">{{ $ev->venue }}</small>
                    </td>
                    <td>
                        <span class="badge info">{{ $ev->criteria?->code ?? 'N/A' }}</span>
                    </td>
                    <td>{{ $item['capacity'] }}</td>
                    <td>
                        <strong>{{ $item['confirmed'] }}</strong>
                        @if($item['waitlist'] > 0)
                            <small class="muted">(+{{ $item['waitlist'] }} chờ)</small>
                        @endif
                    </td>
                    <td><strong style="color: #15803d;">{{ $item['attended_complete'] }}</strong></td>
                    <td>
                        @if($item['attended_incomplete'] > 0)
                            <strong style="color: #d97706;">{{ $item['attended_incomplete'] }}</strong>
                        @else
                            0
                        @endif
                    </td>
                    <td><span class="muted">{{ $item['absent'] }}</span></td>
                    <td><span class="muted">{{ $item['unrecorded'] }}</span></td>
                    <td>
                        <strong style="color: {{ $item['attendance_rate'] >= 70 ? '#15803d' : '#d97706' }};">
                            {{ $item['attendance_rate'] }}%
                        </strong>
                    </td>
                    <td>
                        <strong style="color: #1e3a8a;">{{ $item['points_awarded'] }} đ</strong>
                        <small class="muted" style="display: block;">({{ $item['students_received_points'] }} SV)</small>
                    </td>
                    <td>
                        <a class="btn-sm btn secondary" href="/admin/events/{{ $ev->id }}/export-reconciliation">
                            Xuất CSV
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 30px;" class="muted">
                        Chưa có sự kiện nào trong học kỳ này.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
