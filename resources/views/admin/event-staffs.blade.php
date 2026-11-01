@extends('layout')

@section('title', 'Phân công nhân sự sự kiện')

@section('content')
<a href="/admin/events/{{ $event->id }}/participants">← Trở về danh sách sự kiện</a>

<div class="heading">
    <div>
        <small class="eyebrow">PHÂN QUYỀN VÀ TRÁCH NHIỆM THEO SỰ KIỆN</small>
        <h1>Phân công nhân sự Ban tổ chức</h1>
        <p><strong>{{ $event->title }}</strong> · Tách biệt vai trò người quét mã điểm danh và người duyệt điểm rèn luyện.</p>
    </div>
</div>

<div class="detail">
    <section class="panel">
        <h2>Phân quyền nhân sự mới</h2>
        <form method="POST" action="/admin/events/{{ $event->id }}/staffs">
            @csrf

            <label>Chọn thành viên / Sinh viên hỗ trợ:
                <select name="user_id" required>
                    <option value="">-- Chọn tài khoản --</option>
                    @foreach($availableUsers as $user)
                        <option value="{{ $user->id }}">
                            {{ $user->name }} ({{ $user->student_code ?? $user->email }}) - {{ $user->role }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>Vai trò được phân công trong sự kiện:
                <select name="role" required>
                    <option value="scanner">Người điểm danh (Scanner) - Quét QR và nhập danh sách giấy</option>
                    <option value="approver">Người duyệt điểm (Approver) - Phê duyệt điểm rèn luyện có thẩm quyền</option>
                    <option value="manager">Trưởng ban tổ chức sự kiện (Manager) - Toàn quyền quản trị sự kiện này</option>
                </select>
            </label>

            <button class="btn">Phân công nhiệm vụ</button>
        </form>

        <div class="notice" style="margin-top: 25px;">
            <strong>Nguyên tắc tách biệt quyền:</strong>
            <p>Người được gán vai trò <code>scanner</code> chỉ có thể truy cập màn hình quét QR và nhập giấy, không thể duyệt điểm rèn luyện. Người có quyền <code>approver</code> mới có thẩm quyền xét duyệt danh sách đủ điều kiện.</p>
        </div>
    </section>

    <section class="panel">
        <h2>Danh sách nhân sự đã phân công</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Họ và tên</th>
                        <th>MSSV / Email</th>
                        <th>Vai trò đảm nhiệm</th>
                        <th>Người phân công</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffs as $staff)
                        <tr>
                            <td><strong>{{ $staff->user->name }}</strong></td>
                            <td>{{ $staff->user->student_code ?? $staff->user->email }}</td>
                            <td>
                                @if($staff->role === 'scanner')
                                    <span class="badge info">Điểm danh (Scanner)</span>
                                @elseif($staff->role === 'approver')
                                    <span class="badge green">Duyệt điểm (Approver)</span>
                                @else
                                    <span class="badge purple">Quản lý (Manager)</span>
                                @endif
                            </td>
                            <td><small>{{ $staff->assigner->name }}</small></td>
                            <td>
                                <form method="POST" action="/admin/event-staffs/{{ $staff->id }}" onsubmit="return confirm('Hủy quyền của nhân sự này khỏi sự kiện?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-sm btn danger">Gỡ bỏ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted" style="text-align: center; padding: 25px;">
                                Chưa có nhân sự riêng nào được phân công (Chỉ Admin hệ thống có quyền).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
