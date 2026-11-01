@extends('layout')

@section('title', 'Xem trước dữ liệu Import')

@section('content')
<a href="/admin/events/{{ $event->id }}/import">← Tải lại file khác</a>

<div class="heading">
    <div>
        <small class="eyebrow">XÁC MINH DỮ LIỆU TRƯỚC KHI LƯU CSDL</small>
        <h1>Xem trước & Sàng lọc dữ liệu Import</h1>
        <p><strong>{{ $event->title }}</strong> · Kiểm tra kỹ danh sách trước khi đưa vào hệ thống chính thức.</p>
    </div>
    @if($preview['valid_count'] > 0)
        <form method="POST" action="/admin/events/{{ $event->id }}/import/commit">
            @csrf
            <button class="btn green" style="background: #15803d; color: white;" onclick="return confirm('Xác nhận lưu {{ $preview['valid_count'] }} bản ghi hợp lệ vào sự kiện?');">
                Xác nhận Nhập {{ $preview['valid_count'] }} sinh viên
            </button>
        </form>
    @endif
</div>

<div class="stat-grid-4">
    <div class="panel" style="padding: 15px;">
        <small class="muted">Tổng số dòng đọc được</small>
        <h2 style="margin: 5px 0;">{{ $preview['total'] }}</h2>
    </div>
    <div class="panel" style="padding: 15px;">
        <small class="muted">Hợp lệ để nhập</small>
        <h2 style="margin: 5px 0; color: #15803d;">{{ $preview['valid_count'] }}</h2>
    </div>
    <div class="panel" style="padding: 15px;">
        <small class="muted">Bị trùng / Không hợp lệ</small>
        <h2 style="margin: 5px 0; color: #b91c1c;">{{ $preview['duplicate_count'] }}</h2>
    </div>
</div>

<div class="panel table-wrap">
    <table>
        <thead>
            <tr>
                <th>Dòng</th>
                <th>MSSV</th>
                <th>Họ và tên</th>
                <th>Lớp</th>
                <th>Email</th>
                <th>Thời gian nguồn</th>
                <th>Đánh giá kiểm tra</th>
            </tr>
        </thead>
        <tbody>
            @foreach($preview['rows'] as $r)
                <tr style="{{ !$r['is_valid'] ? 'background: #fff1f2;' : '' }}">
                    <td>#{{ $r['row_num'] }}</td>
                    <td class="mono"><strong>{{ $r['student_code'] }}</strong></td>
                    <td>{{ $r['student_name'] ?: '(Tự động gán theo MSSV)' }}</td>
                    <td>{{ $r['class_name'] }}</td>
                    <td>{{ $r['email'] }}</td>
                    <td><small class="muted">{{ $r['source_time'] }}</small></td>
                    <td>
                        @if($r['is_valid'])
                            <span class="badge green">Hợp lệ</span>
                        @else
                            <span class="badge danger">{{ $r['note'] }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
