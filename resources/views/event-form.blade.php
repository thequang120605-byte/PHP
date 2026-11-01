@extends('layout')

@section('title', 'Biểu mẫu sự kiện')

@section('content')
<a href="/admin">← Trở về bảng quản trị</a>

<div class="page-heading">
    <h1>{{ $e->exists ? 'Chỉnh sửa sự kiện' : 'Tạo sự kiện mới' }}</h1>
    <p class="muted">Nhập giờ Việt Nam. Dữ liệu lưu trữ chuẩn UTC và lưu lại lịch sử Audit Log.</p>
</div>

<section class="panel">
    <form method="POST" enctype="multipart/form-data" action="{{ $e->exists ? '/admin/events/'.$e->id : '/admin/events' }}">
        @csrf
        @if($e->exists) @method('PUT') @endif

        <label>Tên sự kiện
            <input name="title" value="{{ old('title', $e->title) }}" required maxlength="180">
        </label>

        <div class="two">
            <label>Danh mục
                <select name="category_id" required>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id', $e->category_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Địa điểm
                <select name="venue" required>@foreach(\App\Enums\EventVenue::cases() as $venue)<option value="{{ $venue->value }}" @selected(old('venue',$e->venue)===$venue->value)>{{ $venue->value }}</option>@endforeach</select>
            </label>
        </div>

        <div class="two">
            <label>Học kỳ áp dụng
                <select name="semester_id">
                    <option value="">-- Chưa gán học kỳ --</option>
                    @foreach($semesters as $sem)
                        <option value="{{ $sem->id }}" @selected(old('semester_id', $e->semester_id) == $sem->id)>
                            {{ $sem->name }} ({{ $sem->code }})
                        </option>
                    @endforeach
                </select>
            </label>

            <label>Tiêu chí rèn luyện
                <select name="criteria_id">
                    <option value="">-- Chọn tiêu chí đánh giá --</option>
                    @foreach($criterias as $crit)
                        <option value="{{ $crit->id }}" @selected(old('criteria_id', $e->criteria_id) == $crit->id)>
                            [{{ $crit->code }}] {{ $crit->name }} (Trần: {{ $crit->max_points }} đ)
                        </option>
                    @endforeach
                </select>
            </label>
        </div>

        <label>Mô tả chi tiết nội dung
            <textarea name="description" rows="5" required>{{ old('description', $e->description) }}</textarea>
        </label>

        <div class="two">
            <label>Quy tắc điểm danh
<input type="hidden" name="attendance_rule" value="end_only"><p>Điểm danh cuối sự kiện qua đợt 10 phút do BTC mở.</p>
            </label>


        </div>

        <div class="two">
            @foreach(['opens_at'=>'Mở đăng ký','closes_at'=>'Đóng đăng ký','starts_at'=>'Bắt đầu tổ chức','ends_at'=>'Kết thúc'] as $f=>$label)
                <label>{{ $label }}
                    <input type="datetime-local" name="{{ $f }}" value="{{ old($f, $e->$f?->copy()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i')) }}" required>
                </label>
            @endforeach

            <label>Hạn tiếp nhận khiếu nại sai sót (Tùy chọn)
                <input type="datetime-local" name="dispute_closes_at" value="{{ old('dispute_closes_at', $e->dispute_closes_at?->copy()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i')) }}">
                <small class="muted">Nếu để trống, mặc định mở trong vòng 7 ngày sau khi kết thúc sự kiện.</small>
            </label>

            <label>Sức chứa tối đa (Capacity)
                <input type="number" name="capacity" min="1" max="100000" value="{{ old('capacity', $e->capacity) }}" required>
            </label>

            <label>Điểm rèn luyện đề xuất (Proposed points)
                <input type="number" name="proposed_points" min="0" max="20" value="{{ old('proposed_points', $e->proposed_points) }}" required>
            </label>

            <label>Trạng thái công bố
                <select name="status">
                    @foreach(['draft'=>'Bản nháp','published'=>'Công bố chính thức','cancelled'=>'Đã hủy'] as $v=>$label)
                        <option value="{{ $v }}" @selected(old('status', $e->status) === $v)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label>Ảnh poster JPG, PNG, WEBP tối đa 2 MB
                <input type="file" name="poster" accept="image/jpeg,image/png,image/webp">
            </label>
        </div>

        <div style="margin-top: 25px;">
            <button class="btn">Lưu cấu hình sự kiện</button>
        </div>
    </form>
</section>
@endsection
