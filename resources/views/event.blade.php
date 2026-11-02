@extends('layout')

@section('title', $e->title)

@section('content')
<a href="/">← Quay lại danh sách sự kiện</a>

<div class="page-heading">
    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
        <span class="badge">{{ $e->category->name }}</span>
        @if($e->semester)
            <span class="badge info">{{ $e->semester->name }}</span>
        @endif
        @if($e->criteria)
            <span class="badge purple">Tiêu chí: {{ $e->criteria->code }}</span>
        @endif
    </div>
    <h1>{{ $e->title }}</h1>
    <span class="badge {{ $e->isOpen() ? 'green' : '' }}">{{ $e->label() }}</span>
</div>

<div class="detail">
    <section class="panel">
        @if($e->poster_path)
            <img class="poster" src="{{ asset('storage/'.$e->poster_path) }}" alt="Poster">
        @endif

        <h2>Về hoạt động</h2>
        <p class="prose">{{ $e->description }}</p>

        <h2>Quy chế và Điểm danh tham dự</h2>
        <dl>
            <dt>Địa điểm</dt>
            <dd>{{ $e->venue }}</dd>
            <dt>Quy tắc điểm danh</dt>
            <dd>
                <strong>
                    Điểm danh cuối sự kiện
                </strong>

            </dd>
            <dt>Tiêu chí rèn luyện</dt>
            <dd>
                @if($e->criteria)
                    <strong>{{ $e->criteria->code }} — {{ $e->criteria->name }}</strong>
                    <br><small class="muted">Mức trần quy định: {{ $e->criteria->max_points }} điểm / học kỳ (Quy tắc: {{ $e->criteria->rule_version }})</small>
                @else
                    <span class="muted">Chưa gán tiêu chí</span>
                @endif
            </dd>
            @foreach(['opens_at'=>'Mở đăng ký','closes_at'=>'Đóng đăng ký','starts_at'=>'Bắt đầu sự kiện','ends_at'=>'Kết thúc sự kiện'] as $f=>$label)
                <dt>{{ $label }}</dt>
                <dd>{{ $e->$f->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</dd>
            @endforeach
            <dt>Hạn phản hồi sai sót</dt>
            <dd>
                @if($e->dispute_closes_at)
                    {{ $e->dispute_closes_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                @else
                    Trong vòng 7 ngày sau khi kết thúc sự kiện
                @endif
            </dd>
        </dl>

        @auth
            @if(now()->gte($e->ends_at) && $e->isDisputeOpen())
                <div class="notice" style="margin-top: 25px;">
                    <strong>Bạn gặp sự cố về điểm danh hoặc điểm rèn luyện?</strong>
                    <p>Nếu bạn đã tham gia mà thiếu giờ ra hoặc chưa thấy điểm, hãy gửi minh chứng để BTC đối soát.</p>
                    <a class="btn-sm btn secondary" style="margin-top: 8px; display: inline-block;" href="/events/{{ $e->id }}/disputes/create">
                        Gửi phản hồi / Nộp minh chứng
                    </a>
                </div>
            @endif
        @endauth
    </section>

    <aside class="panel">
        <small class="eyebrow">TÌNH TRẠNG CHỖ & ĐIỂM</small>
        <h2>{{ max(0, $e->capacity - $e->confirmed_count) }} chỗ còn lại / {{ $e->capacity }}</h2>

        <div class="notice">
            <strong>{{ $e->proposed_points }} điểm đề xuất</strong>
            <p>Điểm chỉ được duyệt chính thức cho sinh viên có mặt thực tế và hoàn thành đủ yêu cầu điểm danh.</p>
        </div>

        <!-- Khối công cụ Ban tổ chức (Tách biệt hoàn toàn và phân quyền chính xác từng nút) -->
        @auth
            @php
                $isAdmin = auth()->user()->role === 'admin';
                $isManager = $isAdmin || $e->hasStaffRole(auth()->user(), ['manager']);
                $isScanner = $isAdmin || $e->hasStaffRole(auth()->user(), ['scanner', 'manager']);
                $isApprover = $isAdmin || $e->hasStaffRole(auth()->user(), ['approver', 'manager']);
            @endphp

            @if($isManager || $isScanner || $isApprover)
                <div style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 14px; margin-bottom: 16px;">
                    <small style="font-size: 11px; font-weight: 800; color: #1e40af; letter-spacing: 1px; display: block; margin-bottom: 8px;">CÔNG CỤ BAN TỔ CHỨC</small>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @if($isManager)
                            <a class="btn full btn-sm" href="/admin/events/{{ $e->id }}/participants">Quản lý người tham dự</a>
                        @endif
                        @if($isScanner)
                            <a class="btn full secondary btn-sm" href="/events/{{ $e->id }}/attendance/scan">Mở QR cuối sự kiện</a>
                        @endif
                        @if($isApprover)
                            <a class="btn full secondary btn-sm" href="/events/{{ $e->id }}/points/review">Duyệt điểm rèn luyện</a>
                        @endif
                    </div>
                </div>
            @endif
        @endauth

        <!-- Khối trạng thái đăng ký của cá nhân -->
        @if($reg?->status === 'confirmed')
            <a class="btn full" href="/registrations/{{ $reg->id }}">Mở phiếu đăng ký của bạn</a>
        @elseif($reg?->status === 'waitlist')
            <a class="btn full secondary" href="/registrations/{{ $reg->id }}">Xem vị trí trong danh sách chờ (#{{ $reg->waitlist_position }})</a>
        @elseif(!$e->isOpen())
            <p class="muted">Hiện sự kiện không trong thời gian tiếp nhận đăng ký.</p>
        @elseif($e->confirmed_count >= $e->capacity)
            <div class="notice">
                <strong>Sự kiện đã hết chỗ chính thức!</strong>
                <p>Hiện có {{ $e->waitlist_count }} người đang trong hàng đợi. Bạn có thể tham gia danh sách chờ để tự động nhận vé khi có người hủy.</p>
            </div>
            @auth
                <form method="POST" action="/events/{{ $e->id }}/register">
                    @csrf
                    <input type="hidden" name="join_waitlist" value="1">
                    <button class="btn full warning">Đăng ký vào Danh sách chờ</button>
                </form>
            @else
                <a class="btn full" href="/login">Đăng nhập để vào danh sách chờ</a>
            @endauth
        @else
            @auth
                <form method="POST" action="/events/{{ $e->id }}/register">
                    @csrf
                    <button class="btn full">{{ $reg ? 'Đăng ký lại' : 'Đăng ký giữ chỗ ngay' }}</button>
                </form>
            @else
                <a class="btn full" href="/login">Đăng nhập để đăng ký</a>
            @endauth
        @endif

        <p class="fine">Hệ thống áp dụng khóa hàng CSDL chống quá tải (concurrency lock). Khi hết chỗ, hàng chờ waitlist hoạt động tự động.</p>
    </aside>
</div>
@endsection
