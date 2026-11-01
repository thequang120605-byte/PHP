@extends('layout')
@section('title','Điểm danh kết thúc')
@section('content')
<a href="/events/{{ $event->id }}">← Sự kiện</a><div class="page-heading"><h1>Mở điểm danh kết thúc</h1><p>{{ $event->title }} · {{ $event->venue }}</p><p>Giờ kết thúc: {{ $event->ends_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }}</p></div>
<div class="detail"><section class="panel"><h2>Mã chung tại sự kiện</h2><p class="notice">Chỉ mở sau giờ kết thúc. Đợt có TTL đúng 10 phút; QR thay đổi mỗi 15 giây, mã ảnh cũ chỉ còn hợp lệ tối đa khoảng 30 giây và không vượt hạn đợt.</p>
<form method="POST" action="/events/{{ $event->id }}/attendance/open">@csrf<label>Lý do mở lại nếu có<input type="text" name="reason" maxlength="500" placeholder="Ví dụ khắc phục gián đoạn kết nối"></label><button class="btn">Mở điểm danh kết thúc</button></form>
<div id="qr" style="margin:20px 0;max-width:100%;overflow:hidden"><p>Chưa có đợt đang mở.</p></div><strong id="deadline"></strong><p id="countdown" aria-live="polite"></p>
<form method="POST" action="/events/{{ $event->id }}/attendance/close">@csrf<button class="btn danger">Đóng và thu hồi mã ngay</button></form>
<p class="fine">Không đăng QR lên mạng xã hội. Mã chung không chứng minh sinh viên có mặt đủ toàn bộ thời lượng; BTC cần giám sát và đối chiếu khi cần. Không có cơ chế chống chia sẻ tuyệt đối.</p></section>
<section class="panel"><h2>Kết quả</h2><p>Đã ghi nhận: <strong>{{ $stats['completed'] }}</strong></p><p>Cần xác minh: {{ $stats['needs_verification'] }}</p><p><a href="/events/{{ $event->id }}/attendance/paper">Đối soát giấy dự phòng</a></p><p><a href="/events/{{ $event->id }}/points/review">Duyệt điểm đề xuất</a></p><p><a href="/admin/events/{{ $event->id }}/participants">Danh sách và trạng thái</a></p></section></div>
<script>
let expiry=0, offset=0;
async function refresh(){try{const res=await fetch('/events/{{ $event->id }}/attendance/qr',{headers:{Accept:'application/json'},cache:'no-store'});if(!res.ok)throw Error();const d=await res.json();if(!d.active){document.getElementById('qr').textContent='Chưa mở, đã đóng hoặc hết hạn.';expiry=0;return;}document.getElementById('qr').innerHTML=d.svg;expiry=Date.parse(d.expires_at);offset=Date.parse(d.server_now)-Date.now();document.getElementById('deadline').textContent='Hết hạn: '+new Date(expiry).toLocaleString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'});}catch{document.getElementById('qr').textContent='Không tải được mã. Kiểm tra kết nối.';expiry=0;}}
refresh();setInterval(refresh,15000);setInterval(()=>{if(!expiry)return;const n=Math.max(0,Math.ceil((expiry-Date.now()-offset)/1000));document.getElementById('countdown').textContent='Còn '+Math.floor(n/60)+':'+String(n%60).padStart(2,'0');if(n===0){document.getElementById('qr').textContent='Đợt điểm danh đã hết hạn.';expiry=0;}},1000);
</script>
@endsection
