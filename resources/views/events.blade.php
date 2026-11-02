@extends('layout')

@section('content')
<!-- Banner lớn (Hero Section - Deep Navy to Cyan Gradient + 3D Glass Card) -->
<section class="hero">
    <div class="hero-content">
        <small>TRƯỜNG ĐẠI HỌC CÔNG THƯƠNG TP. HỒ CHÍ MINH · HUIT EVENTPASS</small>
        <h1>Hành trình sinh viên<br>bắt đầu từ một hoạt động.</h1>
        <p>Khám phá các chuyên đề học thuật, workshop kỹ năng và chiến dịch tình nguyện.<br>Đăng ký giữ chỗ minh bạch, xác nhận QR khép kín và tích lũy điểm rèn luyện.</p>
        <a class="btn-hero" href="#events">
            Khám phá sự kiện ngay
            <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" />
            </svg>
        </a>
    </div>

    <!-- 3D Glassmorphism Ticket Card -->
    <div class="ticket-3d">
        <div class="ticket-3d-header">
            <span>HUIT EVENTS</span>
            <span style="font-size: 13px;">✦</span>
        </div>
        <div class="ticket-3d-qr">
            <!-- Inline SVG QR Code chuẩn -->
            <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="80" height="80" fill="#ffffff"/>
                <!-- Finders -->
                <rect x="6" y="6" width="22" height="22" rx="3" stroke="#0f172a" stroke-width="3"/>
                <rect x="12" y="12" width="10" height="10" fill="#0f172a"/>
                <rect x="52" y="6" width="22" height="22" rx="3" stroke="#0f172a" stroke-width="3"/>
                <rect x="58" y="12" width="10" height="10" fill="#0f172a"/>
                <rect x="6" y="52" width="22" height="22" rx="3" stroke="#0f172a" stroke-width="3"/>
                <rect x="12" y="58" width="10" height="10" fill="#0f172a"/>
                <!-- Matrix dots -->
                <rect x="36" y="10" width="8" height="8" fill="#0f172a"/>
                <rect x="34" y="24" width="6" height="6" fill="#0f172a"/>
                <rect x="44" y="34" width="8" height="8" fill="#0f172a"/>
                <rect x="16" y="36" width="6" height="6" fill="#0f172a"/>
                <rect x="36" y="44" width="6" height="6" fill="#0f172a"/>
                <rect x="54" y="40" width="8" height="8" fill="#0f172a"/>
                <rect x="64" y="54" width="6" height="6" fill="#0f172a"/>
                <rect x="40" y="62" width="8" height="8" fill="#0f172a"/>
                <rect x="56" y="64" width="6" height="6" fill="#0f172a"/>
            </svg>
        </div>
        <b>YOUR PASS</b>
        <small>NEXT EXPERIENCE · 2026</small>
    </div>
</section>

<!-- Hàng thẻ số liệu (Thống kê - 16px Border Radius + Soft Shadow + Icons) -->
<div class="stats">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg style="width: 26px; height: 26px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
            </svg>
        </div>
        <div class="stat-info">
            <strong>{{ $events->total() }}</strong>
            <span>Sự kiện phù hợp</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <svg style="width: 26px; height: 26px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
        </div>
        <div class="stat-info">
            <strong>01</strong>
            <span>Tài khoản tích hợp MSSV</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon cyan">
            <svg style="width: 26px; height: 26px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <div class="stat-info">
            <strong>24/7</strong>
            <span>Minh chứng & Tra cứu điểm</span>
        </div>
    </div>
</div>

<!-- Phần Tìm kiếm & Danh sách sự kiện -->
<div class="heading" id="events">
    <div>
        <small class="eyebrow" style="color: #f97316;">ĐỪNG BỎ LỠ</small>
        <h2>Sự kiện dành cho bạn</h2>
    </div>
    <span class="muted">Thời gian hiển thị theo giờ Việt Nam (UTC+7)</span>
</div>

<form class="filters-bar" method="GET">
    <div class="search-input-wrap">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input name="q" value="{{ request('q') }}" placeholder="Tìm kiếm tên sự kiện…" aria-label="Tên sự kiện">
    </div>

    <div class="select-wrap">
        <select name="category" aria-label="Danh mục">
            <option value="">Tất cả danh mục</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>

    <label class="check-label">
        <input type="checkbox" name="open" value="1" @checked(request('open') === '1')>
        <span>Chỉ xem sự kiện đang mở</span>
    </label>

    <button class="btn-filter-search">
        <svg style="width: 15px; height: 15px;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        Tìm kiếm
    </button>

    <a href="/" class="btn-filter-reset">Đặt lại</a>
</form>

<div class="grid">
    @forelse($events as $e)
        <article class="card">
            <a class="cover c{{ $e->category_id % 4 }}" href="/events/{{ $e->id }}">
                @if($e->poster_path)
                    <img src="{{ asset('storage/'.$e->poster_path) }}" alt="Poster sự kiện">
                @else
                    <span class="symbol">{{ ['✦','◈','◎','✳'][$e->category_id % 4] }}</span>
                    <b>{{ $e->category->name }}</b>
                @endif

                <div class="date-pill">
                    <strong>{{ $e->starts_at->timezone('Asia/Ho_Chi_Minh')->format('d') }}</strong>
                    THÁNG {{ $e->starts_at->timezone('Asia/Ho_Chi_Minh')->format('m') }}
                </div>
            </a>

            <div class="card-body">
                <div class="meta">
                    <span class="badge {{ $e->isOpen() ? 'green' : '' }}">{{ $e->label() }}</span>
                    <span class="points-chip">⭐ Tối đa {{ $e->proposed_points }} điểm đề xuất</span>
                </div>

                <h3><a href="/events/{{ $e->id }}">{{ $e->title }}</a></h3>

                <div class="card-venue">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    <span>Địa điểm: {{ $e->venue }}</span>
                </div>

                <div class="card-foot">
                    <span class="capacity-tag">{{ $e->confirmed_count }}/{{ $e->capacity }} chỗ</span>
                    <a class="detail-link" href="/events/{{ $e->id }}">
                        Chi tiết ↗
                    </a>
                </div>
            </div>
        </article>
    @empty
        <div class="empty-state-card" style="grid-column: 1 / -1;">
            <div class="empty-state-badge">
                <img src="{{ asset('images/logo-huit.png') }}" alt="Logo HUIT">
            </div>
            <div class="empty-state-watermark">
                <img src="{{ asset('images/logo-huit.png') }}" alt="HUIT Watermark">
            </div>
            <span class="eyebrow" style="color: #2563eb;">TRƯỜNG ĐẠI HỌC CÔNG THƯƠNG TP. HỒ CHÍ MINH</span>
            <h3>Hiện chưa có sự kiện nào trong danh sách hiển thị</h3>
            <p>Hệ thống hiện tại chưa có sự kiện phù hợp với điều kiện tìm kiếm hoặc dữ liệu mẫu chưa được nạp. Hãy thử bỏ chọn bộ lọc để xem toàn bộ danh mục.</p>
            <div class="empty-state-actions">
                <a href="/" class="btn">
                    <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Xem tất cả sự kiện
                </a>
                @if(auth()->user()?->role === 'admin')
                    <a href="/admin/events/create" class="btn secondary">
                        + Tạo sự kiện mới ngay
                    </a>
                @endif
            </div>
        </div>
    @endforelse
</div>

<div class="pagination">
    {{ $events->links('pagination::simple-default') }}
</div>
@endsection
