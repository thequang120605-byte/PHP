@extends('layout')

@section('title', 'Xử lý phản hồi sai sót & Đối soát')

@section('content')
<a href="/admin">← Trở về bảng quản trị</a>

<div class="heading">
    <div>
        <small class="eyebrow">MODULE ĐỐI SOÁT & KHIẾU NẠI</small>
        <h1>Xử lý phản hồi sai sót từ sinh viên</h1>
        <p>Giải quyết các trường hợp: thiếu tên điểm danh, thiếu giờ ra (kèm ảnh minh chứng), đủ điều kiện nhưng chưa nhận điểm, hoặc điểm bị sai.</p>
    </div>
</div>

<div class="filters">
    <form method="GET" action="/admin/disputes" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <select name="status">
            <option value="">-- Tất cả trạng thái --</option>
            <option value="pending" @selected($status === 'pending')>Chờ xử lý</option>
            <option value="in_review" @selected($status === 'in_review')>Đang xác minh</option>
            <option value="resolved" @selected($status === 'resolved')>Đã chấp nhận</option>
            <option value="rejected" @selected($status === 'rejected')>Đã từ chối</option>
        </select>

        <select name="event_id">
            <option value="">-- Tất cả sự kiện --</option>
            @foreach($events as $ev)
                <option value="{{ $ev->id }}" @selected($eventId == $ev->id)>{{ Str::limit($ev->title, 40) }}</option>
            @endforeach
        </select>

        <button class="btn secondary">Lọc danh sách</button>
    </form>
</div>

<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Mã Ticket</th>
                <th>Sinh viên</th>
                <th>Sự kiện</th>
                <th>Vấn đề & Nội dung</th>
                <th>Minh chứng</th>
                <th>Trạng thái hiện tại</th>
                <th>Xử lý phê duyệt</th>
            </tr>
        </thead>
        <tbody>
            @forelse($disputes as $d)
                <tr>
                    <td class="mono"><strong>{{ $d->ticket_code }}</strong></td>
                    <td>
                        <strong>{{ $d->user->name }}</strong>
                        <small class="mono">{{ $d->user->student_code }} · {{ $d->user->class_name }}</small>
                    </td>
                    <td>
                        <a href="/events/{{ $d->event_id }}">{{ Str::limit($d->event->title, 30) }}</a>
                    </td>
                    <td>
                        <strong style="color: #1e3a8a;">{{ $d->typeLabel() }}</strong>
                        <p style="margin: 4px 0; font-size: 12px; color: #475569;">{{ $d->description }}</p>
                        <small class="muted">Gửi lúc {{ $d->created_at->format('d/m/Y H:i') }}</small>
                    </td>
                    <td>
                        @if($d->evidence_file_path)
                            <a href="/disputes/{{ $d->id }}/evidence" target="_blank" class="btn-sm btn secondary">Xem file</a>
                        @else
                            <span class="muted">Không có</span>
                        @endif
                    </td>
                    <td>
                        @switch($d->status)
                            @case('pending') <span class="badge warning">Chờ xử lý</span> @break
                            @case('in_review') <span class="badge info">Đang xác minh</span> @break
                            @case('resolved') <span class="badge green">Chấp nhận</span> @break
                            @case('rejected') <span class="badge danger">Từ chối</span> @break
                        @endswitch
                    </td>
                    <td>
                        <details>
                            <summary style="cursor: pointer; color: #2563eb; font-size: 12px; font-weight: 600;">Cập nhật xử lý</summary>
                            <form method="POST" action="/admin/disputes/{{ $d->id }}/process" style="margin-top: 8px; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; min-width: 260px;">
                                @csrf
                                <label style="font-size: 11px; margin-bottom: 6px;">Trạng thái:
                                    <select name="status" style="padding: 4px; font-size: 12px;">
                                        <option value="pending" @selected($d->status === 'pending')>Chờ xử lý</option>
                                        <option value="in_review" @selected($d->status === 'in_review')>Đang xác minh</option>
                                        <option value="resolved" @selected($d->status === 'resolved')>Chấp nhận (Giải quyết)</option>
                                        <option value="rejected" @selected($d->status === 'rejected')>Từ chối yêu cầu</option>
                                    </select>
                                </label>
                                <label style="font-size: 11px; margin-bottom: 6px;">Ghi chú kết luận (bắt buộc):
                                    <input type="text" name="resolution_notes" value="{{ $d->resolution_notes }}" placeholder="VD: Đã đối chiếu ảnh minh chứng hợp lệ, công nhận giờ ra" required style="padding: 4px 8px; font-size: 12px;">
                                </label>
                                <button class="btn btn-sm" style="margin-top: 6px;">Lưu kết luận</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;" class="muted">
                        Không có khiếu nại sai sót nào phù hợp bộ lọc.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    {{ $disputes->links('pagination::simple-default') }}
</div>
@endsection
