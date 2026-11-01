@extends('layout')

@section('title', 'Đăng ký của tôi')

@section('content')
<div class="page-heading">
    <small class="eyebrow">DẤU MỐC CỦA BẠN</small>
    <h1>Đăng ký của tôi</h1>
    <p class="muted">Phiếu xác nhận giữ chỗ và lịch sử tham gia sự kiện được lưu trữ bảo đảm đối soát.</p>
</div>

<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Sự kiện</th>
                <th>Thời gian đăng ký (VN)</th>
                <th>Trạng thái chỗ</th>
                <th style="text-align: right;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registrations as $r)
                <tr>
                    <td style="font-weight: 600; color: #0f172a;">{{ $r->event->title }}</td>
                    <td>{{ $r->submitted_at ? $r->submitted_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') : 'N/A' }}</td>
                    <td>
                        @if($r->status === 'confirmed')
                            <span class="badge green">Đã xác nhận</span>
                        @elseif($r->status === 'waitlist')
                            <span class="badge warning">Danh sách chờ #{{ $r->waitlist_position }}</span>
                        @else
                            <span class="badge danger">Đã hủy</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <a class="btn secondary btn-sm" href="/registrations/{{ $r->id }}">Xem phiếu →</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 32px; color: #64748b;">
                        Bạn chưa đăng ký sự kiện nào. <a href="/" style="font-weight: 600;">Khám phá sự kiện ngay →</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    {{ $registrations->links('pagination::simple-default') }}
</div>
@endsection
