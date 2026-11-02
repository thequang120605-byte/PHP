@extends('layout')

@section('title', 'Sự kiện tôi phụ trách · Không gian làm việc BTC')

@section('content')
<div class="page-heading">
    <small class="eyebrow">KHÔNG GIAN LÀM VIỆC BAN TỔ CHỨC</small>
    <h1>Sự kiện tôi phụ trách</h1>
    <p>Không gian vận hành dành riêng cho nhân sự được phân công vai trò: Scanner (Điểm danh), Approver (Duyệt điểm) và Manager (Trưởng BTC).</p>
</div>

<!-- Bộ lọc vai trò -->
<div style="display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; align-items: center;">
    <span class="muted" style="font-size: 13px; font-weight: 600; margin-right: 4px;">Lọc theo vai trò:</span>
    <a href="/staff/events" class="category-pill {{ empty($roleFilter) ? 'active' : '' }}">
        Tất cả công việc ({{ $events->total() }})
    </a>
    <a href="/staff/events?role=scanner" class="category-pill {{ $roleFilter === 'scanner' ? 'active' : '' }}">
        📱 Scanner (Điểm danh)
    </a>
    <a href="/staff/events?role=approver" class="category-pill {{ $roleFilter === 'approver' ? 'active' : '' }}">
        ⚖️ Approver (Duyệt điểm)
    </a>
    <a href="/staff/events?role=manager" class="category-pill {{ $roleFilter === 'manager' ? 'active' : '' }}">
        👔 Manager (Trưởng BTC)
    </a>
</div>

@if(session('pending_disputes_warning'))
    <div class="alert warning" style="margin-bottom: 24px; border-left: 4px solid #f59e0b;">
        <h4 style="margin: 0 0 6px 0; color: #b45309;">⚠️ Cảnh báo: Còn khiếu nại chưa giải quyết!</h4>
        <p style="margin: 0 0 12px 0;">Sự kiện này hiện vẫn còn <strong>{{ session('pending_disputes_warning')['count'] }} khiếu nại</strong> của sinh viên đang ở trạng thái chờ xử lý.</p>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="/admin/disputes" class="btn warning" style="font-size: 13px;">
                Xem và xử lý khiếu nại →
            </a>
            <form method="POST" action="/events/{{ session('pending_disputes_warning')['event_id'] }}/finalize" style="margin: 0;">
                @csrf
                <input type="hidden" name="force_finalize" value="1">
                <input type="hidden" name="notes" value="Chốt kết quả cưỡng chế khi còn khiếu nại tồn đọng theo phê duyệt Trưởng BTC.">
                <button class="btn secondary" style="font-size: 13px;" onclick="return confirm('Bạn có chắc chắn muốn chốt kết quả sự kiện dù vẫn còn khiếu nại chưa xử lý?');">
                    Vẫn tiếp tục chốt sổ
                </button>
            </form>
        </div>
    </div>
@endif

<div style="display: flex; flex-direction: column; gap: 20px;">
    @forelse($events as $event)
        <div class="panel" style="border-radius: 14px; position: relative; overflow: hidden; border: 1px solid {{ $event->is_finalized ? '#86efac' : '#e2e8f0' }}; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
            
            @if($event->is_finalized)
                <div style="position: absolute; top: 0; right: 0; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700; padding: 4px 12px; border-bottom-left-radius: 8px; border-left: 1px solid #86efac; border-bottom: 1px solid #86efac;">
                    🔒 ĐÃ CHỐT SỔ KẾT QUẢ SỰ KIỆN
                </div>
            @endif

            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <!-- Danh sách các vai trò của user đối với sự kiện này -->
                    <div style="display: flex; gap: 6px; margin-bottom: 8px; flex-wrap: wrap;">
                        @foreach($event->my_roles as $role)
                            @if($role === 'scanner')
                                <span class="badge blue" style="font-size: 11px; font-weight: 700;">📱 Scanner · Điểm danh</span>
                            @elseif($role === 'approver')
                                <span class="badge green" style="font-size: 11px; font-weight: 700;">⚖️ Approver · Duyệt điểm</span>
                            @elseif($role === 'manager')
                                <span class="badge purple" style="font-size: 11px; font-weight: 700;">👔 Manager · Quản lý & Chốt</span>
                            @endif
                        @endforeach
                        
                        <span class="badge {{ $event->isOpen() ? 'green' : (now()->gt($event->ends_at) ? 'danger' : 'warning') }}" style="font-size: 11px;">
                            {{ $event->label() }}
                        </span>
                    </div>

                    <h2 style="margin: 0 0 6px 0; font-size: 18px; color: #0f172a;">
                        <a href="/events/{{ $event->id }}" style="color: inherit; text-decoration: none;">
                            {{ $event->title }}
                        </a>
                    </h2>

                    <div style="font-size: 12.5px; color: #64748b; display: flex; gap: 14px; flex-wrap: wrap;">
                        <span>📍 <strong>{{ $event->venue }}</strong></span>
                        <span>🕒 Diễn ra: <strong>{{ $event->starts_at->format('H:i d/m/Y') }} – {{ $event->ends_at->format('H:i d/m/Y') }}</strong></span>
                        <span>🏷️ Tiêu chí: <strong>{{ $event->criteria?->code ?? 'Chưa cấu hình' }} (+{{ $event->proposed_points }} ĐRL)</strong></span>
                    </div>
                </div>

                <!-- 4 Chỉ số nhanh -->
                <div style="display: flex; gap: 12px; background: #f8fafc; padding: 10px 16px; border-radius: 10px; border: 1px solid #e2e8f0; text-align: center;">
                    <div>
                        <small class="muted" style="font-size: 10.5px; display: block; text-transform: uppercase;">Đăng ký</small>
                        <strong style="font-size: 16px; color: #1e40af;">{{ $event->confirmed_count }}</strong>
                    </div>
                    <div style="border-left: 1px solid #cbd5e1; padding-left: 12px;">
                        <small class="muted" style="font-size: 10.5px; display: block; text-transform: uppercase;">Đã Check-in</small>
                        <strong style="font-size: 16px; color: #0891b2;">{{ $event->completion_count }}</strong>
                    </div>
                    <div style="border-left: 1px solid #cbd5e1; padding-left: 12px;">
                        <small class="muted" style="font-size: 10.5px; display: block; text-transform: uppercase;">Đã Duyệt</small>
                        <strong style="font-size: 16px; color: #15803d;">{{ $event->approved_points_count }}</strong>
                    </div>
                    <div style="border-left: 1px solid #cbd5e1; padding-left: 12px;">
                        <small class="muted" style="font-size: 10.5px; display: block; text-transform: uppercase;">Chờ Duyệt</small>
                        <strong style="font-size: 16px; color: {{ $event->pending_points_count > 0 ? '#d97706' : '#94a3b8' }};">
                            {{ $event->pending_points_count }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- CHECKLIST VẬN HÀNH 5 BƯỚC CỦA SỰ KIỆN -->
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; border: 1px solid #e2e8f0;">
                <div style="font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px;">
                    QUY TRÌNH VẬN HÀNH KIỂM SOÁT
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; font-size: 12px;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: {{ $event->checklist['has_staff'] ? '#15803d' : '#94a3b8' }}; font-weight: 800;">
                            {{ $event->checklist['has_staff'] ? '✓' : '○' }}
                        </span>
                        <span>1. Phân công BTC ({{ $event->staffs->count() }} người)</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: {{ $event->checklist['has_rule'] ? '#15803d' : '#ef4444' }}; font-weight: 800;">
                            {{ $event->checklist['has_rule'] ? '✓' : '✕' }}
                        </span>
                        <span>2. Gán học kỳ & tiêu chí</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: {{ $event->checklist['has_attendance'] ? '#15803d' : '#94a3b8' }}; font-weight: 800;">
                            {{ $event->checklist['has_attendance'] ? '✓' : '○' }}
                        </span>
                        <span>3. Điểm danh QR ({{ $event->completion_count }} SV)</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: {{ $event->approved_points_count > 0 ? '#15803d' : '#94a3b8' }}; font-weight: 800;">
                            {{ $event->approved_points_count > 0 ? '✓' : '○' }}
                        </span>
                        <span>4. Duyệt điểm ({{ $event->approved_points_count }}/{{ $event->completion_count }})</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="color: {{ $event->is_finalized ? '#15803d' : '#94a3b8' }}; font-weight: 800;">
                            {{ $event->is_finalized ? '🔒' : '○' }}
                        </span>
                        <span>5. Chốt kết quả sự kiện</span>
                    </div>
                </div>
            </div>

            <!-- CÁC NÚT THAO TÁC THEO PHÂN QUYỀN VAI TRÒ -->
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 14px;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    {{-- Thao tác cho Scanner --}}
                    @if(in_array('scanner', $event->my_roles))
                        <a href="/events/{{ $event->id }}/attendance/scan" class="btn" style="padding: 7px 12px; font-size: 12.5px; background: #0284c7;">
                            📱 Mở / Quét QR điểm danh
                        </a>
                        <a href="/events/{{ $event->id }}/attendance/qr" class="btn secondary" style="padding: 7px 12px; font-size: 12.5px;">
                            🖥️ Máy chiếu QR hội trường
                        </a>
                    @endif

                    {{-- Thao tác cho Approver --}}
                    @if(in_array('approver', $event->my_roles))
                        <a href="/events/{{ $event->id }}/points/review" class="btn" style="padding: 7px 12px; font-size: 12.5px; background: #16a34a;">
                            ⚖️ Duyệt điểm rèn luyện ({{ $event->pending_points_count }} chờ)
                        </a>
                    @endif

                    {{-- Thao tác cho Manager --}}
                    @if(in_array('manager', $event->my_roles))
                        <a href="/admin/events/{{ $event->id }}/participants" class="btn secondary" style="padding: 7px 12px; font-size: 12.5px;">
                            📊 Danh sách & Xuất CSV
                        </a>
                        <a href="/admin/events/{{ $event->id }}/staffs" class="btn secondary" style="padding: 7px 12px; font-size: 12.5px;">
                            👥 Phân công BTC
                        </a>
                    @endif
                </div>

                {{-- Nút Chốt kết quả sự kiện (Chỉ Manager hoặc Admin) --}}
                @if(in_array('manager', $event->my_roles))
                    <div>
                        @if($event->is_finalized)
                            <span class="badge green" style="font-size: 12px; padding: 6px 12px;">
                                ✓ Đã chốt sổ kết quả
                            </span>
                        @elseif(now()->lt($event->ends_at))
                            <span class="badge" style="font-size: 12px; color: #64748b; background: #f1f5f9;" title="Chỉ được chốt khi sự kiện đã kết thúc">
                                ⏳ Chốt sổ sau khi kết thúc
                            </span>
                        @else
                            <form method="POST" action="/events/{{ $event->id }}/finalize" style="margin: 0;" onsubmit="return confirm('Bạn có chắc chắn muốn chốt kết quả sự kiện này? Sau khi chốt, mọi điều chỉnh điểm cần ghi rõ lý do.');">
                                @csrf
                                <button class="btn" style="background: #1e3a8a; font-size: 12.5px; padding: 7px 14px;">
                                    🔒 Chốt kết quả sự kiện →
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    @empty
        <div class="panel" style="text-align: center; padding: 40px;">
            <p class="muted">Bạn hiện chưa được phân công phụ trách sự kiện nào.</p>
        </div>
    @endforelse
</div>

<div style="margin-top: 20px;">
    {{ $events->links() }}
</div>
@endsection
