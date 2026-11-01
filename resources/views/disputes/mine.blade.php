@extends('layout')

@section('title', 'Phản hồi sai sót của tôi')

@section('content')
<div class="page-heading">
    <small class="eyebrow">THEO DÕI YÊU CẦU ĐỐI SOÁT</small>
    <h1>Danh sách phản hồi sai sót & Khiếu nại</h1>
    <p>Theo dõi tiến độ giải quyết các sự cố điểm danh, thiếu giờ ra hoặc sai lệch điểm rèn luyện.</p>
</div>

<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Mã Ticket</th>
                <th>Sự kiện liên quan</th>
                <th>Vấn đề</th>
                <th>Minh chứng</th>
                <th>Thời gian gửi</th>
                <th>Trạng thái</th>
                <th>Kết luận của BTC</th>
            </tr>
        </thead>
        <tbody>
            @forelse($disputes as $d)
                <tr>
                    <td class="mono"><strong>{{ $d->ticket_code }}</strong></td>
                    <td>
                        <a href="/events/{{ $d->event_id }}"><strong>{{ $d->event->title }}</strong></a>
                    </td>
                    <td>
                        <strong>{{ $d->typeLabel() }}</strong>
                        <small class="muted" style="display: block; max-width: 250px;">{{ Str::limit($d->description, 80) }}</small>
                    </td>
                    <td>
                        @if($d->evidence_file_path)
                            <a href="/disputes/{{ $d->id }}/evidence" target="_blank" class="btn-sm btn secondary">Xem minh chứng</a>
                        @else
                            <span class="muted">Không có file</span>
                        @endif
                    </td>
                    <td>{{ $d->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td>
                    <td>
                        @switch($d->status)
                            @case('pending') <span class="badge warning">Chờ xử lý</span> @break
                            @case('in_review') <span class="badge info">Đang xác minh</span> @break
                            @case('resolved') <span class="badge green">Chấp nhận</span> @break
                            @case('rejected') <span class="badge danger">Từ chối</span> @break
                            @default <span class="badge">{{ $d->status }}</span>
                        @endswitch
                    </td>
                    <td>
                        @if($d->resolution_notes)
                            <p style="margin: 0; font-size: 12px; color: #334155;">{{ $d->resolution_notes }}</p>
                            <small class="muted">Xử lý lúc {{ $d->handled_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</small>
                        @else
                            <span class="muted">Đang chờ BTC kiểm tra</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;" class="muted">
                        Bạn chưa gửi phản hồi sai sót nào.
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
