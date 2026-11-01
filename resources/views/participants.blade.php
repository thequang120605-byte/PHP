@extends('layout')

@section('title', 'Quản lý người tham dự & Điểm danh')

@section('content')
<a href="/admin">← Trở về bảng quản trị</a>

<div class="heading">
    <div>
        <h1>Quản lý người tham dự & Điểm danh</h1>
        <p><strong>{{ $event->title }}</strong> · Quy tắc: Điểm danh cuối sự kiện · Tiêu chí: {{ $event->criteria?->code ?? 'Chưa gán' }}</p>
    </div>
    <div class="actions">
        <a class="btn secondary btn-sm" href="/admin/events/{{ $event->id }}/staffs">Phân công nhân sự</a>
        <a class="btn secondary btn-sm" href="/admin/events/{{ $event->id }}/import">Import CSV / Excel</a>
        <a class="btn secondary btn-sm" href="/admin/events/{{ $event->id }}/export-reconciliation">Xuất CSV Đối soát (BOM)</a>
    </div>
</div>

<div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
    <a class="btn" href="/events/{{ $event->id }}/attendance/scan">Mở QR cuối sự kiện</a>
    <a class="btn secondary" href="/events/{{ $event->id }}/attendance/paper">Nhập danh sách giấy dự phòng</a>
    
    <form method="POST" action="/events/{{ $event->id }}/attendance/reconcile" style="display: inline-block;">
        @csrf
        <button class="btn secondary" title="Tự động kiểm tra điều kiện vào/ra để xác nhận trạng thái hoàn thành">
            Chạy đối soát tự động
        </button>
    </form>

    <a class="btn green" style="background: #15803d; color: white;" href="/events/{{ $event->id }}/points/review">
        Duyệt điểm rèn luyện
    </a>
</div>

<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Họ tên & MSSV</th>
                <th>Lớp</th>
                <th>Thời gian đăng ký</th>
                <th>Trạng thái</th>
                <th>Điểm danh</th>
                <th>Ghi nhận hoàn thành</th>
                <th>Bằng chứng</th>
                <th>Nguồn</th>
                <th>Điểm RL</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registrations as $r)
                @php
                    $user = $r->user;
                    $pt = $recordedPoints->get($user->id);
                @endphp
                <tr>
                    <td>
                        <strong>{{ $r->snapshot['name'] ?? $user->name }}</strong>
                        <small class="mono">{{ $r->snapshot['student_code'] ?? $user->student_code }}</small>
                    </td>
                    <td>{{ $r->snapshot['class_name'] ?? $user->class_name }}</td>
                    <td>{{ $r->submitted_at ? $r->submitted_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') : '' }}</td>
                    <td>
                        @if($r->status === 'confirmed')
                            <span class="badge green">Chính thức</span>
                        @elseif($r->status === 'waitlist')
                            <span class="badge warning">Chờ #{{ $r->waitlist_position }}</span>
                        @else
                            <span class="badge danger">Đã hủy</span>
                        @endif
                    </td>
                    <td>
                        @if($r->completion)
                            <span class="badge green">Hoàn thành</span>
                        @elseif(in_array($r->attendance_status, ['needs_verification', 'attended_complete', 'attended_incomplete']))
                            <span class="badge warning">Cần xác minh</span>
                        @elseif($r->attendance_status === 'absent')
                            <span class="badge danger">Vắng mặt</span>
                        @else
                            <span class="badge">Chưa điểm danh</span>
                        @endif
                    </td>
                    <td>{{ $r->completion?->completed_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i:s d/m') ?? '-' }}</td>
                    <td>{{ $r->completion?->method ?? '-' }}</td>
                    <td>
                        <span class="mono" style="font-size: 11px;">{{ strtoupper($r->attendance_source) }}</span>
                    </td>
                    <td>
                        @if($pt && $pt->status === 'approved')
                            <strong style="color: #15803d;">+{{ $pt->points }} đ</strong>
                        @elseif($pt && $pt->status === 'revoked')
                            <span class="badge danger">Thu hồi</span>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>
                        <a href="/registrations/{{ $r->id }}">Xem phiếu đăng ký</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 25px;">Chưa có dữ liệu người đăng ký.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    {{ $registrations->links('pagination::simple-default') }}
</div>
@endsection
