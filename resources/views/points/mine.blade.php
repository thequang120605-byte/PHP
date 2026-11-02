@extends('layout')

@section('title', 'Bảng điểm rèn luyện & Tiến độ cá nhân')

@section('content')
<div class="page-heading">
    <small class="eyebrow">KẾT QUẢ RÈN LUYỆN & ĐỊNH HƯỚNG HOẠT ĐỘNG</small>
    <h1>Bảng tiến độ điểm rèn luyện sinh viên</h1>
    <p>Theo dõi điểm thực nhận, tiến độ từng tiêu chí, trần điểm tối đa và gợi ý hoạt động phù hợp.</p>
</div>

@php
    $grandApproved = collect($transcript)->sum('total_approved');
    $grandPending = collect($transcript)->sum('total_pending');
    $grandRevoked = collect($transcript)->sum('total_revoked');
    $targetPoints = 80;
    $progressPct = min(100, round(($grandApproved / $targetPoints) * 100));
@endphp

<!-- Banner Thành tích & Gamification Huy hiệu Sinh viên -->
<div class="gamification-banner" style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px; position: relative; z-index: 2;">
        <div>
            <small style="font-size: 11px; font-weight: 800; color: #38bdf8; letter-spacing: 1.5px; text-transform: uppercase;">TIẾN ĐỘ TÍCH LŨY TOÀN DIỆN</small>
            <h2 style="margin: 4px 0 0; color: #ffffff; font-size: 22px;">
                Tổng điểm rèn luyện chính thức: <span style="color: #4ade80; font-weight: 800;">{{ $grandApproved }} điểm</span>
            </h2>
        </div>
        <div>
            @if($grandApproved >= 80)
                <span class="badge-pill gold">⭐ Xuất sắc · Sinh viên 5 Tốt Tiêu biểu</span>
            @elseif($grandApproved >= 65)
                <span class="badge-pill gold">🥇 Tốt · Thủ lĩnh Phong trào HUIT</span>
            @elseif($grandApproved >= 50)
                <span class="badge-pill">🥈 Khá · Tích cực tham gia</span>
            @else
                <span class="badge-pill">🥉 Trung bình · Cần bổ sung hoạt động</span>
            @endif
        </div>
    </div>

    <!-- 4 Khối Chỉ số Tách bạch -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 14px; position: relative; z-index: 2;">
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 10px; padding: 12px 14px;">
            <div style="font-size: 11px; color: #86efac; text-transform: uppercase; font-weight: 700;">🟢 ĐÃ DUYỆT CHÍNH THỨC</div>
            <div style="font-size: 24px; font-weight: 800; color: #ffffff; margin-top: 2px;">{{ $grandApproved }} <small style="font-size: 13px; font-weight: 500; color: #a7f3d0;">điểm</small></div>
            <div style="font-size: 11px; color: #cbd5e1; margin-top: 2px;">Đã cộng vào học kỳ</div>
        </div>

        <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 10px; padding: 12px 14px;">
            <div style="font-size: 11px; color: #fde047; text-transform: uppercase; font-weight: 700;">🟡 ĐANG CHỜ DUYỆT</div>
            <div style="font-size: 24px; font-weight: 800; color: #ffffff; margin-top: 2px;">+{{ $grandPending }} <small style="font-size: 13px; font-weight: 500; color: #fef08a;">điểm</small></div>
            <div style="font-size: 11px; color: #cbd5e1; margin-top: 2px;">Đã quét QR, chờ BTC chốt</div>
        </div>

        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 10px; padding: 12px 14px;">
            <div style="font-size: 11px; color: #fca5a5; text-transform: uppercase; font-weight: 700;">🔴 ĐÃ THU HỒI / ĐIỀU CHỈNH</div>
            <div style="font-size: 24px; font-weight: 800; color: #ffffff; margin-top: 2px;">{{ $grandRevoked }} <small style="font-size: 13px; font-weight: 500; color: #fecaca;">điểm</small></div>
            <div style="font-size: 11px; color: #cbd5e1; margin-top: 2px;">Kèm lý do minh bạch</div>
        </div>

        <div style="background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 10px; padding: 12px 14px;">
            <div style="font-size: 11px; color: #7dd3fc; text-transform: uppercase; font-weight: 700;">🎯 MỤC TIÊU ĐẠT CHUẨN</div>
            <div style="font-size: 24px; font-weight: 800; color: #ffffff; margin-top: 2px;">{{ $grandApproved }}/{{ $targetPoints }} <small style="font-size: 13px; font-weight: 500; color: #bae6fd;">({{ $progressPct }}%)</small></div>
            <div style="font-size: 11px; color: #cbd5e1; margin-top: 2px;">Còn thiếu: {{ max(0, $targetPoints - $grandApproved) }} điểm</div>
        </div>
    </div>

    <!-- Thanh tiến độ tổng thể -->
    <div style="background: rgba(255,255,255,0.15); height: 8px; border-radius: 6px; overflow: hidden; margin-bottom: 6px; position: relative; z-index: 2;">
        <div style="background: linear-gradient(90deg, #38bdf8, #4ade80); height: 100%; width: {{ $progressPct }}%; border-radius: 6px; transition: width 0.5s ease;"></div>
    </div>
</div>

<!-- Bộ lọc Học kỳ -->
<div class="filters" style="margin-bottom: 24px;">
    <form method="GET" action="/my-points" style="display: flex; gap: 10px; align-items: center; width: 100%;">
        <label style="margin: 0; min-width: 260px;">
            <select name="semester_id" onchange="this.form.submit()" style="padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1;">
                <option value="">-- Tất cả học kỳ --</option>
                @foreach($semesters as $s)
                    <option value="{{ $s->id }}" @selected($semesterId == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn secondary">Lọc học kỳ</button>
    </form>
</div>

@forelse($transcript as $data)
    <div class="panel" style="margin-bottom: 28px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="margin: 0; color: #1e3a8a; font-size: 20px;">{{ $data['semester']->name }}</h2>
                <small class="muted">Mã học kỳ: <strong>{{ $data['semester']->code }}</strong> · Thời gian: {{ $data['semester']->starts_at->format('d/m/Y') }} – {{ $data['semester']->ends_at->format('d/m/Y') }}</small>
            </div>
            <div style="text-align: right;">
                <span class="muted" style="font-size: 11px; text-transform: uppercase;">Tổng điểm học kỳ này:</span>
                <div style="font-size: 26px; font-weight: 800; color: #15803d;">
                    {{ $data['total_approved'] }} <span style="font-size: 15px; font-weight: 600;">/ {{ $data['max_possible'] }} điểm</span>
                </div>
            </div>
        </div>

        <!-- KHỐI GỢI Ý HOẠT ĐỘNG THÔNG MINH (RECOMMENDATIONS) -->
        @if(!empty($data['recommendations']))
            <div style="background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border: 1px solid #93c5fd; border-radius: 12px; padding: 18px; margin-bottom: 24px; position: relative;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                    <span style="font-size: 20px;">💡</span>
                    <div>
                        <strong style="color: #1e40af; font-size: 15px;">Gợi ý hoạt động để tích lũy tiêu chí còn thiếu</strong>
                        <div style="font-size: 12px; color: #475569;">Hệ thống phân tích các tiêu chí bạn chưa đạt trần điểm và đề xuất hoạt động phù hợp nhất:</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
                    @foreach($data['recommendations'] as $rec)
                        <div style="background: #ffffff; border-radius: 10px; padding: 14px; border: 1px solid #bfdbfe; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.06); display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                                    <span class="badge blue" style="font-size: 10.5px;">{{ $rec->criteria->code }} · {{ $rec->criteria->name }}</span>
                                    <strong style="color: #15803d; font-size: 13px;">+{{ $rec->proposed_points }} ĐRL</strong>
                                </div>
                                <h4 style="margin: 6px 0 8px; font-size: 14px; color: #0f172a; line-height: 1.4;">
                                    <a href="/events/{{ $rec->id }}" style="color: inherit; text-decoration: none;">{{ $rec->title }}</a>
                                </h4>
                                <div style="font-size: 11.5px; color: #b45309; background: #fffbeb; padding: 6px 8px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #f59e0b;">
                                    {{ $rec->recommend_reason }}
                                </div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 10px; margin-top: auto;">
                                <small class="muted">📍 {{ $rec->venue }}</small>
                                <a href="/events/{{ $rec->id }}" class="btn" style="padding: 6px 12px; font-size: 12px;">
                                    Giữ chỗ ngay →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- BẢNG TIẾN ĐỘ TỪNG TIÊU CHÍ (CRITERIA PROGRESS CARDS) -->
        <h3 style="font-size: 16px; margin-bottom: 14px; color: #334155;">Tiến độ chi tiết từng tiêu chí rèn luyện</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 14px; margin-bottom: 24px;">
            @foreach($data['by_criteria'] as $critId => $cGroup)
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <div>
                            <span class="badge {{ $cGroup['approved_points'] >= $cGroup['max_points'] ? 'green' : 'blue' }}" style="font-size: 11px;">
                                {{ $cGroup['criteria']->code }}
                            </span>
                            <strong style="display: block; font-size: 13.5px; color: #1e293b; margin-top: 4px; line-height: 1.35;">
                                {{ $cGroup['criteria']->name }}
                            </strong>
                        </div>
                        <div style="text-align: right; flex-shrink: 0; margin-left: 10px;">
                            <span style="font-size: 16px; font-weight: 800; color: {{ $cGroup['approved_points'] >= $cGroup['max_points'] ? '#15803d' : '#1e40af' }};">
                                {{ $cGroup['approved_points'] }}
                            </span>
                            <span class="muted" style="font-size: 12px;">/ {{ $cGroup['max_points'] }}đ</span>
                        </div>
                    </div>

                    <!-- Thanh Progress Bar Tiêu chí -->
                    <div style="background: #e2e8f0; height: 7px; border-radius: 4px; overflow: hidden; margin-bottom: 8px;">
                        <div style="background: {{ $cGroup['approved_points'] >= $cGroup['max_points'] ? '#10b981' : '#3b82f6' }}; height: 100%; width: {{ $cGroup['percentage'] }}%; border-radius: 4px;"></div>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 11.5px; color: #64748b;">
                        <span>Tiến độ: <strong>{{ $cGroup['percentage'] }}%</strong></span>
                        @if($cGroup['gap'] == 0)
                            <span style="color: #15803d; font-weight: 700;">✓ Đạt trần tiêu chí</span>
                        @else
                            <span style="color: #b45309; font-weight: 600;">Còn thiếu: {{ $cGroup['gap'] }} điểm</span>
                        @endif
                    </div>

                    @if($cGroup['pending_points'] > 0)
                        <div style="margin-top: 8px; font-size: 11px; background: #fffbeb; color: #92400e; padding: 4px 8px; border-radius: 4px;">
                            ⏳ Có <strong>+{{ $cGroup['pending_points'] }} điểm</strong> đang chờ BTC duyệt
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- BẢNG LỊCH SỬ GHI NHẬN & GIẢI TRÌNH ĐIỂM THỰC NHẬN -->
        <h3 style="font-size: 16px; margin-bottom: 12px; color: #334155;">Lịch sử hoạt động & Giải trình điểm thực nhận</h3>
        
        <div class="table-wrap">
            <table style="background: white; border-radius: 8px; overflow: hidden;">
                <thead>
                    <tr>
                        <th>Tên hoạt động / Sự kiện</th>
                        <th>Tiêu chí</th>
                        <th>Thời gian</th>
                        <th>Điểm ghi nhận</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú & Giải trình trần điểm</th>
                    </tr>
                </thead>
                <tbody>
                    @php $hasRows = false; @endphp
                    @foreach($data['by_criteria'] as $cGroup)
                        <!-- Điểm đã duyệt hoặc đã thu hồi -->
                        @foreach($cGroup['items'] as $item)
                            @php $hasRows = true; @endphp
                            <tr>
                                <td>
                                    <a href="/events/{{ $item->event_id }}" style="font-weight: 600; color: #1e40af;">
                                        {{ $item->event->title }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge blue" style="font-size: 10.5px;">{{ $item->criteria->code }}</span>
                                </td>
                                <td>{{ $item->event->starts_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <strong style="color: {{ $item->status === 'approved' ? '#15803d' : '#b91c1c' }}; font-size: 15px;">
                                        +{{ $item->points }} đ
                                    </strong>
                                </td>
                                <td>
                                    @if($item->status === 'approved')
                                        <span class="badge green">Đã duyệt</span>
                                    @else
                                        <span class="badge danger">Đã thu hồi</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->capping_note)
                                        <div style="font-size: 11px; color: #b45309; background: #fffbeb; padding: 4px 8px; border-radius: 4px; border-left: 2px solid #f59e0b; margin-bottom: 4px;">
                                            🔒 {{ $item->capping_note }}
                                        </div>
                                    @endif

                                    @if($item->notes)
                                        <small style="color: #64748b; display: block;">{{ $item->notes }}</small>
                                    @endif

                                    @if($item->adjustments->isNotEmpty())
                                        <small style="color: #7c3aed; display: block; margin-top: 2px;">
                                            (Đã qua {{ $item->adjustments->count() }} lần điều chỉnh)
                                        </small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        <!-- Các ca đang chờ duyệt -->
                        @foreach($cGroup['pending_items'] as $pItem)
                            @php $hasRows = true; @endphp
                            <tr style="background: #fafaf9;">
                                <td>
                                    <a href="/events/{{ $pItem->event_id }}" style="font-weight: 600; color: #475569;">
                                        {{ $pItem->event->title }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge blue" style="font-size: 10.5px;">{{ $pItem->event->criteria->code }}</span>
                                </td>
                                <td>{{ $pItem->event->starts_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <strong style="color: #d97706; font-size: 15px;">
                                        +{{ $pItem->event->proposed_points }} đ
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge warning">Chờ BTC duyệt</span>
                                </td>
                                <td>
                                    <small style="color: #d97706;">Đã quét QR cuối sự kiện lúc {{ $pItem->completed_at->format('H:i d/m/Y') }}. Đang trong quy trình đối soát.</small>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    @if(!$hasRows)
                        <tr>
                            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">
                                Chưa có hoạt động nào được ghi nhận trong học kỳ này. Hãy khám phá và đăng ký các sự kiện bên trên!
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="panel" style="text-align: center; padding: 40px;">
        <p class="muted">Chưa có dữ liệu học kỳ nào.</p>
    </div>
@endforelse

<div class="notice">
    <strong>Lưu ý nghiệp vụ HUIT EventPass:</strong> Điểm rèn luyện từ các hoạt động Đoàn – Hội, Khoa và CLB là dữ liệu gốc đối soát trực tiếp theo quy định trần tiêu chí và học kỳ. Điểm được ghi nhận khép kín từ check-in thực tế tại hội trường, được bảo đảm bằng snapshot quy tắc tại thời điểm hoàn thành và chữ ký kiểm toán.
</div>
@endsection
