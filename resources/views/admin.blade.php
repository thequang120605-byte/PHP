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

<div class="stats stats--admin">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg style="width: 26px; height: 26px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
        </div>
        <div class="stat-info">
            <strong>{{ $events->total() }}</strong>
            <span>Sự kiện tổ chức</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cyan">
            <svg style="width: 26px; height: 26px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" />
            </svg>
        </div>
        <div class="stat-info">
            <strong>{{ $registrations }}</strong>
            <span>Đăng ký giữ chỗ</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg style="width: 26px; height: 26px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
            </svg>
        </div>
        <div class="stat-info">
            <strong style="color: #15803d;">{{ $totalPoints }}</strong>
            <span>Điểm RL đã cấp</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon {{ $pendingDisputes > 0 ? 'red' : 'blue' }}" style="{{ $pendingDisputes > 0 ? 'background: #fee2e2; color: #dc2626;' : '' }}">
            <svg style="width: 26px; height: 26px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>
        <div class="stat-info">
            <strong style="color: {{ $pendingDisputes > 0 ? '#b91c1c' : '#0284c7' }};">{{ $pendingDisputes }}</strong>
            <span>Khiếu nại chờ xử lý</span>
        </div>
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
