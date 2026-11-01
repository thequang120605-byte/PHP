# Kết quả kiểm thử bản review

Ngày kiểm tra: 02/11/2026 (giờ Việt Nam). PHP 8.3.6, Laravel 12 theo composer.lock; MariaDB 10.11.14/InnoDB cho kiểm tra khóa hàng.

## Tự động

**42 tests passed, 186 assertions.** Chạy bằng `php artisan test`, SQLite in-memory độc lập database cấu hình của người dùng.

- 17 test nền: đăng nhập, hash mật khẩu, phân quyền, khóa tài khoản, đăng ký/cửa sổ thời gian/capacity, snapshot/audit, hủy/idempotency, quyền phiếu, CRUD/upload và CSV.
- 25 test luồng mới: mở sau ends_at, TTL 600 giây; mốc 599/600; double-click không gia hạn; đóng/mở lại cùng giây; reason; thu hồi/hủy; slot và chữ ký giả; duplicate; waitlist/locked/unknown; chống giả user_id; sai sự kiện; scanner/approver; endpoint cũ; no-store; secret hidden; giấy sai MSSV; chứng cứ và thu hồi điểm; tổng trần khác sự kiện/điều chỉnh; khiếu nại không tạo giờ giả; private file; luật/venue; waitlist không cấp QR; import binding; seed; batch tiếp theo; báo cáo không suy ra hoàn thành từ trạng thái cũ.

Test cũ AdvancedFeaturesTest kiểm tra check-in/out và QR cá nhân không còn phù hợp yêu cầu mới; lưu dạng tài liệu `docs/legacy_tests/AdvancedFeaturesTest.php.txt`, không chạy như test hồi quy. Bộ EndAttendanceTest thay thế các hành vi này; không tuyên bố toàn bộ assertion cũ vẫn còn hiệu lực.

Composer validate thành công; Blade compile thành công; PHP syntax check các file app/bootstrap/config/database/routes/tests thành công. QR SVG từ BaconQrCode được giải mã độc lập bằng zxing-cpp, bao gồm QR chụp từ trang trình duyệt thực.

## Trình duyệt

Chromium headless, desktop 1440×1000, mobile 390×844. Đã chạy admin đăng nhập → mở QR sự kiện đã kết thúc → đọc QR → sinh viên đăng nhập/xác nhận → admin duyệt → sinh viên xem bảng điểm. Không ghi nhận lỗi JavaScript; màn hình xác nhận mobile không tràn ngang. Đã xem screenshot để kiểm tra bố cục. Kết quả máy đọc trong browser-results.json.

## Thử cạnh tranh trên MariaDB thực

Database thử riêng, migrate và seed mới. 100 tài khoản tổng hợp; pool 20 worker chạy tiến trình PHP gọi service song song, 120 lần gọi gồm 20 retry cùng sinh viên. Hai worker duyệt hai sự kiện cho cùng một sinh viên với trần tiêu chí 5.

| Hậu điều kiện | Kết quả |
|---|---|
| Lượt ghi nhận | 120 |
| Sinh viên khác nhau | 100 |
| Lượt duplicate | 20 |
| Lượt lỗi | 0 |
| Bản ghi hoàn thành sự kiện chính | 100 |
| Tổng điểm sau duyệt đồng thời hai sự kiện | 5 / trần 5 |

Chi tiết JSON kèm số thời gian đo: mysql-concurrency-results.json. Buffer pool thử nghiệm 128 MB, môi trường container dùng chung. Thời gian tiến trình gồm cả PHP bootstrap, không phải HTTP latency. Không dùng con số đó làm SLA.

## Giới hạn

Không kiểm thử 40.000 người dùng đồng thời, không đo tải HTTP hàng trăm sinh viên, không kiểm thử toàn bộ topology Redis/PHP-FPM/load balancer hoặc MySQL 8 riêng. MariaDB giúp xác nhận luồng cạnh tranh InnoDB thực nhưng vẫn cần chạy migration/test trên phiên bản MySQL của Laragon nhóm. Chưa xác nhận dữ liệu/quy định chính thức HUIT, quyền sở hữu định danh sinh viên, attendance Meet/Teams hay tích hợp cổng trường. Test không chứng minh chống chia sẻ mã trực tiếp hoặc tham dự trọn thời lượng.

Trước nâng cấp dữ liệu thật, thử trên bản sao và làm đúng HUONG_DAN_NANG_CAP.md; không dùng migrate:fresh.
