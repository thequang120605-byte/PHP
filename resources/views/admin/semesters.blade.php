@extends('layout')

@section('title', 'Quản lý học kỳ')

@section('content')
<a href="/admin">← Trở về bảng quản trị</a>

<div class="heading">
    <div>
        <small class="eyebrow">MODULE ĐIỂM RÈN LUYỆN</small>
        <h1>Quản lý Học kỳ & Khung thời gian</h1>
        <p>Thiết lập các học kỳ để gắn sự kiện và tính điểm rèn luyện theo quy chế.</p>
    </div>
</div>

<div class="detail">
    <section class="panel">
        <h2>Thêm mới / Cập nhật Học kỳ</h2>
        <form method="POST" action="/admin/semesters">
            @csrf

            <label>Mã học kỳ (Code duy nhất):
                <input type="text" name="code" placeholder="VD: 2026_HK1" required maxlength="30">
            </label>

            <label>Tên học kỳ:
                <input type="text" name="name" placeholder="VD: Học kỳ 1 (Năm học 2026 - 2027)" required maxlength="100">
            </label>

            <div class="two">
                <label>Ngày bắt đầu:
                    <input type="date" name="starts_at" required>
                </label>
                <label>Ngày kết thúc:
                    <input type="date" name="ends_at" required>
                </label>
            </div>

            <label class="check" style="margin: 15px 0;">
                <input type="checkbox" name="is_active" value="1" checked>
                Đang là học kỳ hoạt động (Mở xét duyệt)
            </label>

            <button class="btn">Lưu học kỳ</button>
        </form>
    </section>

    <section class="panel">
        <h2>Danh sách Học kỳ</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên học kỳ</th>
                        <th>Thời gian</th>
                        <th>Số sự kiện</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($semesters as $s)
                        <tr>
                            <td class="mono"><strong>{{ $s->code }}</strong></td>
                            <td>{{ $s->name }}</td>
                            <td><small>{{ $s->starts_at->format('d/m/Y') }} – {{ $s->ends_at->format('d/m/Y') }}</small></td>
                            <td><span class="badge info">{{ $s->events_count }} sự kiện</span></td>
                            <td>
                                @if($s->is_active)
                                    <span class="badge green">Đang mở</span>
                                @else
                                    <span class="badge">Đã đóng</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;" class="muted">Chưa có học kỳ nào được thiết lập.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
