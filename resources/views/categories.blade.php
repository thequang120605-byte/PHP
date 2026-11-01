@extends('layout')

@section('title', 'Danh mục sự kiện')

@section('content')
<a href="/admin">← Trở về bảng quản trị</a>

<div class="page-heading">
    <small class="eyebrow">CẤU HÌNH HỆ THỐNG</small>
    <h1>Danh mục sự kiện</h1>
    <p class="muted">Phân loại các hoạt động ngoại khóa, hội thảo, tình nguyện và học thuật.</p>
</div>

<section class="panel" style="max-width: 800px;">
    <form method="POST" action="/admin/categories" style="display: flex; flex-direction: row; gap: 12px; margin-bottom: 24px;">
        @csrf
        <input style="flex: 1;" type="text" name="name" required maxlength="100" placeholder="Nhập tên danh mục sự kiện mới...">
        <button type="submit" class="btn" style="white-space: nowrap;">+ Thêm danh mục</button>
    </form>

    <div style="border-top: 1px solid #e2e8f0; padding-top: 10px;">
        @foreach($categories as $c)
            <div class="category-row">
                <form class="filters" method="POST" action="/admin/categories/{{ $c->id }}" style="margin: 0;">
                    @csrf
                    @method('PUT')
                    <input type="text" name="name" value="{{ $c->name }}" required maxlength="100" aria-label="Tên danh mục" style="flex: 1;">
                    <span class="badge info" style="white-space: nowrap;">{{ $c->events_count }} sự kiện</span>
                    <button type="submit" class="btn secondary btn-sm">Lưu</button>
                </form>
                <form method="POST" action="/admin/categories/{{ $c->id }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');" style="margin: 0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-btn red">Xóa</button>
                </form>
            </div>
        @endforeach
    </div>
</section>
@endsection
