@extends('layout')

@section('title', 'Import danh sách Excel / CSV')

@section('content')
<a href="/admin/events/{{ $event->id }}/participants">← Trở về danh sách sự kiện</a>

<div class="heading">
    <div>
        <small class="eyebrow">XỬ LÝ DỮ LIỆU NGOẠI VI & CHỐNG TRÙNG</small>
        <h1>Import danh sách đăng ký từ Excel / CSV</h1>
        <p><strong>{{ $event->title }}</strong> · Hỗ trợ dữ liệu từ Google Forms hoặc file Excel ngoài. Quy trình gồm: Tải lên → Xem trước kiểm tra trùng → Xác nhận nhập.</p>
    </div>
</div>

<div class="detail">
    <section class="panel">
        <h2>Bước 1: Tải file dữ liệu nguồn</h2>
        <form method="POST" enctype="multipart/form-data" action="/admin/events/{{ $event->id }}/import/preview">
            @csrf

            <label>Chọn file CSV / TXT nguồn (Dung lượng tối đa 10 MB):
                <input type="file" name="file" accept=".csv,.txt" required>
            </label>

            <div class="notice">
                <strong>Yêu cầu cấu trúc file CSV UTF-8 (Tối đa 1.000 dòng / 10 MB):</strong>
                <p>File CSV cần có tiêu đề cột gồm: <code>mssv</code> (bắt buộc) và <code>source_time</code> (thời gian ghi nhận nguồn, định dạng <code>YYYY-MM-DD HH:MM:SS</code> hoặc <code>DD/MM/YYYY HH:MM:SS</code>). Các cột tùy chọn: <code>name</code>, <code>class_name</code>, <code>email</code>.<br>
                <em>Mẹo xuất file từ Excel:</em> Mở Excel, chọn <strong>File → Save As → CSV UTF-8 (Comma delimited) (*.csv)</strong> để giữ đúng mã hóa tiếng Việt.<br>
                Hệ thống sẽ kiểm tra trùng lặp trên cơ sở dữ liệu thực tế và lưu file gốc phục vụ hậu kiểm.</p>
            </div>

            <button class="btn">Tải lên & Xem trước dữ liệu (Preview)</button>
        </form>
    </section>

    <section class="panel">
        <h2>Lịch sử file đã import</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tên file nguồn</th>
                        <th>Người thực hiện</th>
                        <th>Hợp lệ</th>
                        <th>Bị trùng</th>
                        <th>Thời gian nhập</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pastImports as $imp)
                        <tr>
                            <td><strong>{{ $imp->original_name }}</strong></td>
                            <td>{{ $imp->user->name }}</td>
                            <td><span class="badge green">{{ $imp->success_rows }}</span></td>
                            <td><span class="badge warning">{{ $imp->duplicate_rows }}</span></td>
                            <td>{{ $imp->imported_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted" style="text-align: center; padding: 20px;">Chưa có file nào được import cho sự kiện này.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
