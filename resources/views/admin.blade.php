@extends('layout')

@section('title', 'Trung tâm Quản trị')

@section('content')
<div class="heading">
    <div>
        <small class="eyebrow">KHÔNG GIAN QUẢN TRỊ TOÀN DIỆN</small>
        <h1>Tổng quan và Điều hành Hoạt động</h1>
        <p>Hệ thống quản lý chu trình: Đăng ký → Điểm danh QR & Giấy → Đối soát → Duyệt điểm rèn luyện → Xử lý khiếu nại.</p>
    </div>
    <div class="actions">
        <a class="btn" href="/admin/events/create">+ Tạo sự kiện mới</a>
    </div>
</div>

<div class="stats" style="grid-template-columns: repeat(4, 1fr);">
    <div>
        <strong>{{ $events->total() }}</strong>
        <span>Sự kiện tổ chức</span>
    </div>
    <div>
        <strong>{{ $registrations }}</strong>
        <span>Đăng ký giữ chỗ</span>
    </div>
    <div>
        <strong style="color: #15803d;">{{ $totalPoints }}</strong>
        <span>Điểm RL đã cấp</span>
    </div>
    <div>
        <strong style="color: {{ $pendingDisputes > 0 ? '#b91c1c' : '#0284c7' }};">{{ $pendingDisputes }}</strong>
        <span>Khiếu nại chờ xử lý</span>
    </div>
</div>

<div style="display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap;">
    <a class="btn secondary btn-sm" href="/admin/semesters">Quản lý Học kỳ</a>
    <a class="btn secondary btn-sm" href="/admin/criterias">Tiêu chí Rèn luyện</a>
    <a class="btn secondary btn-sm" href="/admin/disputes">Xử lý Phản hồi sai sót ({{ $pendingDisputes }})</a>
    <a class="btn secondary btn-sm" href="/admin/users">Quản lý Tài khoản & Khóa</a>
    <a class="btn secondary btn-sm" href="/admin/reports">Báo cáo Thống kê Tổng thể</a>
</div>

<div class="panel table-wrap">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="margin: 0;">Danh sách sự kiện</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tên sự kiện</th>
                <th>Danh mục & Tiêu chí</th>
                <th>Trạng thái</th>
                <th>Sức chứa</th>
                <th>Quy tắc điểm danh</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $e)
                <tr>
                    <td>
                        <strong>{{ $e->title }}</strong>
                        <small class="muted" style="display: block;">{{ $e->venue }}</small>
                    </td>
                    <td>
                        <span class="badge">{{ $e->category->name }}</span>
                        @if($e->criteria)
                            <span class="badge info">{{ $e->criteria->code }}</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $e->isOpen() ? 'green' : '' }}">{{ $e->label() }}</span></td>
                    <td><strong>{{ $e->confirmed_count }}/{{ $e->capacity }}</strong></td>
                    <td>
                        <small>Điểm danh cuối sự kiện</small>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="/admin/events/{{ $e->id }}/participants" class="btn-sm btn secondary">Người tham dự</a>
                            <a href="/events/{{ $e->id }}/attendance/scan" class="btn-sm btn secondary">Mở QR cuối buổi</a>
                            <a href="/admin/events/{{ $e->id }}/edit">Sửa</a>
                            <form method="POST" action="/admin/events/{{ $e->id }}">
                                @csrf @method('DELETE')
                                <button class="text-btn red" onclick="return confirm('Xóa sự kiện này?');">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 25px;" class="muted">Chưa có sự kiện nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">{{ $events->links('pagination::simple-default') }}</div>

<section class="panel table-wrap" style="margin-top: 30px;">
    <h2>Nhật ký Audit Log thời gian thực (Hệ thống kiểm toán)</h2>
    <table>
        <thead>
            <tr>
                <th>Thời gian VN</th>
                <th>Người thực hiện</th>
                <th>Hành động</th>
                <th>Thực thể</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $l)
                <tr>
                    <td>{{ $l->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }}</td>
                    <td><strong>{{ $l->actor?->name ?? 'Hệ thống' }}</strong></td>
                    <td><span class="badge">{{ $l->action }}</span></td>
                    <td><span class="mono">{{ $l->entity_type }} #{{ $l->entity_id }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endsection
