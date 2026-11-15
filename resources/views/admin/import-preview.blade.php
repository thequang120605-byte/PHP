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
    <div class="panel" style="padding: 16px;">
        <small class="muted">Tổng số dòng đọc được</small>
        <h2 style="margin: 5px 0;">{{ $preview['total'] }}</h2>
    </div>
    <div class="panel" style="padding: 16px;">
        <small class="muted">Hợp lệ để nhập</small>
        <h2 style="margin: 5px 0; color: #15803d;">{{ $preview['valid_count'] }}</h2>
    </div>
    <div class="panel" style="padding: 16px;">
        <small class="muted">Bị trùng / Không hợp lệ</small>
        <h2 style="margin: 5px 0; color: #b91c1c;">{{ $preview['duplicate_count'] }}</h2>
    </div>
</div>

<!-- Bộ lọc hàng xem trước -->
<div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
    <button type="button" class="btn btn-sm" id="btn-filter-all" onclick="filterPreviewRows('all')" style="background: #2563eb; color: white;">
        Tất cả ({{ $preview['total'] }})
    </button>
    <button type="button" class="btn btn-sm white" id="btn-filter-valid" onclick="filterPreviewRows('valid')" style="border: 1px solid #cbd5e1;">
        Chỉ xem Hợp lệ ({{ $preview['valid_count'] }})
    </button>
    <button type="button" class="btn btn-sm white" id="btn-filter-invalid" onclick="filterPreviewRows('invalid')" style="border: 1px solid #cbd5e1;">
        Chỉ xem Lỗi / Trùng ({{ $preview['duplicate_count'] }})
    </button>
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
        <tbody id="preview-tbody">
            @foreach($preview['rows'] as $r)
                <tr class="preview-row" data-valid="{{ $r['is_valid'] ? '1' : '0' }}" style="{{ !$r['is_valid'] ? 'background: #fff1f2;' : '' }}">
                    <td>#{{ $r['row_num'] }}</td>
                    <td class="mono"><strong>{{ $r['student_code'] }}</strong></td>
                    <td>{{ $r['student_name'] ?: '(Chưa có tài khoản sinh viên trên hệ thống)' }}</td>
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

@if($preview['valid_count'] > 0)
    <!-- Thanh xác nhận nổi cố định phía dưới khi cuộn (Sticky Action Bar) -->
    <div style="position: sticky; bottom: 20px; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(8px); padding: 14px 24px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); z-index: 50; margin-top: 24px; color: white;">
        <div>
            <strong style="font-size: 15px; color: #86efac;">Sẵn sàng nhập dữ liệu:</strong>
            <span style="font-size: 13px; color: #cbd5e1; margin-left: 6px;">{{ $preview['valid_count'] }} bản ghi hợp lệ</span>
        </div>
        <form method="POST" action="/admin/events/{{ $event->id }}/import/commit" style="margin: 0;">
            @csrf
            <button class="btn green" style="background: #16a34a; font-weight: 700; padding: 10px 22px;" onclick="return confirm('Xác nhận lưu {{ $preview['valid_count'] }} bản ghi hợp lệ vào sự kiện?');">
                ✓ Lưu {{ $preview['valid_count'] }} sinh viên vào CSDL
            </button>
        </form>
    </div>
@endif

<script>
function filterPreviewRows(type) {
    const rows = document.querySelectorAll('.preview-row');
    rows.forEach(r => {
        const isValid = r.getAttribute('data-valid') === '1';
        if (type === 'all') {
            r.style.display = '';
        } else if (type === 'valid') {
            r.style.display = isValid ? '' : 'none';
        } else if (type === 'invalid') {
            r.style.display = !isValid ? '' : 'none';
        }
    });

    ['all', 'valid', 'invalid'].forEach(t => {
        const btn = document.getElementById('btn-filter-' + t);
        if (t === type) {
            btn.style.background = '#2563eb';
            btn.style.color = '#ffffff';
        } else {
            btn.style.background = '#ffffff';
            btn.style.color = '#334155';
        }
    });
}
</script>
@endsection
