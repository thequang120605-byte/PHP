@extends('layout')

@section('title', 'Quản lý tiêu chí rèn luyện')

@section('content')
<a href="/admin">← Trở về bảng quản trị</a>

<div class="heading">
    <div>
        <small class="eyebrow">MODULE ĐIỂM RÈN LUYỆN</small>
        <h1>Bộ Tiêu chí rèn luyện & Mức trần điểm</h1>
        <p>Quy định khung tiêu chí đánh giá, phiên bản quy tắc và mức điểm trần tối đa theo quy định trường.</p>
    </div>
</div>

<div class="detail">
    <section class="panel">
        <h2>Thêm mới Tiêu chí đánh giá</h2>
        <form method="POST" action="/admin/criterias">
            @csrf

            <div class="two">
                <label>Mã tiêu chí (Code):
                    <input type="text" name="code" placeholder="VD: TC1.1, TC2.1" required maxlength="30">
                </label>
                <label>Mức trần điểm tối đa trong học kỳ:
                    <input type="number" name="max_points" value="20" min="1" max="100" required>
                </label>
            </div>

            <label>Tên tiêu chí rèn luyện:
                <input type="text" name="name" placeholder="VD: Ý thức học tập, nghiên cứu khoa học và đổi mới sáng tạo" required maxlength="180">
            </label>

            <label>Phiên bản quy tắc / Căn cứ văn bản:
                <input type="text" name="rule_version" value="QĐ-HUIT-2026" required maxlength="50">
            </label>

            <label>Mô tả chi tiết / Hướng dẫn xét:
                <textarea name="description" rows="3" placeholder="Ghi chú quy chế tính điểm..."></textarea>
            </label>

            <button class="btn">Lưu tiêu chí</button>
        </form>
    </section>

    <section class="panel">
        <h2>Danh sách Tiêu chí hiện hành</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mã TC</th>
                        <th>Tên tiêu chí</th>
                        <th>Mức trần</th>
                        <th>Văn bản quy định</th>
                        <th>Số sự kiện</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($criterias as $c)
                        <tr>
                            <td class="mono"><strong>{{ $c->code }}</strong></td>
                            <td>
                                <strong>{{ $c->name }}</strong>
                                @if($c->description)
                                    <small class="muted" style="display: block;">{{ $c->description }}</small>
                                @endif
                            </td>
                            <td><strong style="color: #1e3a8a;">{{ $c->max_points }} đ</strong></td>
                            <td><span class="badge info">{{ $c->rule_version }}</span></td>
                            <td><span class="badge">{{ $c->events_count }} sự kiện</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;" class="muted">Chưa có tiêu chí nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
