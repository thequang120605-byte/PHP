@extends('layout')

@section('title', 'Quản lý tài khoản người dùng')

@section('content')
<a href="/admin">← Trở về bảng quản trị</a>

<div class="heading">
    <div>
        <small class="eyebrow">QUẢN TRỊ TÀI KHOẢN VÀ BẢO VỆ DỮ LIỆU</small>
        <h1>Quản lý tài khoản sinh viên & Phân quyền</h1>
        <p>Kiểm tra danh sách, thông tin MSSV, lớp và kiểm soát khóa / mở khóa tài khoản người dùng.</p>
    </div>
</div>

<div class="filters">
    <form method="GET" action="/admin/users" style="display: flex; gap: 10px; align-items: center; width: 100%; flex-wrap: wrap;">
        <input type="text" name="q" value="{{ $q }}" placeholder="Tìm theo Tên, MSSV, Email, Lớp...">
        
        <select name="role">
            <option value="">-- Tất cả vai trò --</option>
            <option value="student" @selected($role === 'student')>Sinh viên</option>
            <option value="admin" @selected($role === 'admin')>Admin</option>
        </select>

        <select name="status">
            <option value="">-- Trạng thái tài khoản --</option>
            <option value="active" @selected($status === 'active')>Đang hoạt động</option>
            <option value="locked" @selected($status === 'locked')>Đã bị khóa</option>
        </select>

        <button class="btn secondary">Lọc tài khoản</button>
    </form>
</div>

<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Họ và tên</th>
                <th>MSSV</th>
                <th>Lớp</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $u)
                <tr>
                    <td><strong>{{ $u->name }}</strong></td>
                    <td class="mono">{{ $u->student_code ?? '-' }}</td>
                    <td>{{ $u->class_name ?? '-' }}</td>
                    <td>{{ $u->email }}</td>
                    <td>
                        @if($u->role === 'admin')
                            <span class="badge purple">Admin</span>
                        @else
                            <span class="badge">Sinh viên</span>
                        @endif
                    </td>
                    <td>
                        @if($u->is_active)
                            <span class="badge green">Đang hoạt động</span>
                        @else
                            <span class="badge danger">Đã bị khóa</span>
                        @endif
                    </td>
                    <td>{{ $u->created_at->format('d/m/Y') }}</td>
                    <td>
                        @if($u->id !== auth()->id())
                            <form method="POST" action="/admin/users/{{ $u->id }}/toggle-active" onsubmit="return confirm('Bạn có chắc chắn muốn thay đổi trạng thái hoạt động của tài khoản này?');">
                                @csrf
                                @if($u->is_active)
                                    <button class="btn-sm btn danger">Khóa tài khoản</button>
                                @else
                                    <button class="btn-sm btn green" style="background: #15803d; color: white;">Mở khóa</button>
                                @endif
                            </form>
                        @else
                            <small class="muted">Tài khoản hiện tại</small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px;" class="muted">
                        Không tìm thấy tài khoản nào phù hợp điều kiện tìm kiếm.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    {{ $users->links('pagination::simple-default') }}
</div>
@endsection
