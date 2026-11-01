# Review và refactor HUIT EventPass

Phạm vi: mã trong ZIP đính kèm, trọng tâm đăng ký, điểm danh cuối sự kiện, minh chứng và duyệt điểm. Đây là bản đã chỉnh sửa và kiểm thử theo phạm vi ghi dưới đây, không phải chứng nhận an toàn/hoàn thiện 100% cho vận hành toàn trường.

## 1. Kết luận kiến trúc

Code gốc có Controller/Service/Model nhưng ranh giới nghiệp vụ chưa đủ chặt: một số quyết định dựa vào trường giờ vào/ra, kiểm tra quyền chỉ ở HTTP hoặc điều kiện truy vấn tùy chọn dễ bỏ lọc. Sau refactor:

- Controller xử lý HTTP, validation, upload, response/view; không tự quyết định đủ điều kiện hay tính điểm.
- AttendanceService chịu trách nhiệm điều kiện mở, TTL/chữ ký, đăng ký chính thức, chống trùng, transaction và audit.
- PointService duyệt riêng, khóa theo sinh viên, đọc snapshot và kiểm soát tổng trần. Service có kiểm tra quyền để không bị bỏ qua khi gọi từ command/job.
- Model giữ quan hệ, cast, trạng thái và ẩn secret khi serialize. Enum địa điểm dùng chung cho form/validation/seed.
- Database là lớp bảo vệ cuối với FK/unique/index. Chưa cần thêm repository trùng Eloquent; nếu mở rộng API/queue thì tái sử dụng service.

## 2. Phát hiện chính và bản sửa

| Mức | Phát hiện ở bản nhận | Cách sửa trong bản bàn giao |
|---|---|---|
| Nghiêm trọng | Bộ vẽ QR tự dựng từ bit hash không phải QR tiêu chuẩn | Dùng bacon/bacon-qr-code, đã giải mã thử bằng thư viện đọc QR độc lập |
| Nghiêm trọng | Nhập giấy dùng lọc User tùy chọn: MSSV không tồn tại có thể bỏ điều kiện và chọn nhầm đăng ký | Bắt buộc tìm đúng MSSV/role/active/đăng ký confirmed; lỗi rollback cả đợt |
| Cao | QR vé cố định, logic check-in/out không đúng yêu cầu cuối sự kiện | Bỏ endpoint cũ, không cấp QR vĩnh viễn; chỉ BTC mở phiên sau ends_at |
| Cao | Kiểm tra rồi insert thiếu ràng buộc nhất quán khi quét lại | Khóa riêng đăng ký + unique registration và event/user; trả kết quả duplicate |
| Cao | Trần điểm và điều chỉnh có thể bỏ qua điểm ở sự kiện khác hoặc cộng lại điểm đã thu hồi | Khóa cùng user trên mọi đường ghi điểm, tính tổng trong học kỳ/tiêu chí; duyệt không cấp lại bản ghi revoked |
| Cao | Cập nhật trạng thái khiếu nại có thể tạo giờ tham dự không có căn cứ | Không tạo completion tự động; dùng quy trình scan giấy xác minh riêng |
| Cao | Scan/khiếu nại trong public có thể bị tải ngoài quyền | Local private + route kiểm quyền; thêm command chuyển file cũ |
| Cao | Preview import chưa gắn chặt actor/event; tạo tài khoản mật khẩu chung và thiếu giới hạn sức chứa | Bind actor/event/20 phút, commit đọc lại file tin cậy; chỉ user có sẵn, khóa event để kiểm sức chứa |
| Trung bình | Có thể đổi luật/điểm/tiêu chí sau khi đã phát sinh bằng chứng | Snapshot + khóa trường luật; tiêu chí mới phải có phiên bản riêng |
| Trung bình | Mã khiếu nại count+1 và export nhiều truy vấn | ULID; CSV chunk 500, eager load và giới hạn trang review 100 |
| Trung bình | Batch duyệt lặp có thể luôn dừng ở nhóm đã ghi nhận đầu tiên | Lọc hồ sơ đã có student_points khi duyệt đợt tiếp theo, tối đa 200 |
| Vận hành | Gói nguồn chứa môi trường/cache/log có thể lộ cấu hình cũ | ZIP bàn giao bỏ .env, log, session, upload và cache biên dịch; giữ .env.example |

## 3. Luồng QR mới và lựa chọn lưu trữ

1. Sinh viên đăng ký trong cửa sổ thời gian; hệ thống giữ submitted_at và snapshot. Đăng ký không tạo bằng chứng đã tham dự.
2. Sau giờ ends_at, scanner/manager được phân công hoặc admin bấm mở. Chưa mở thì không có mã hoạt động. Cần đổi giờ kết thúc trước khi mở nếu lịch thực tế thay đổi.
3. Transaction khóa event để tránh hai lần mở tạo phiên chồng. Phiên có UUID, random secret 256 bit, opened_at, expires_at = opened_at + 600 giây, revoked_at và snapshot luật. Secret mã hóa bằng APP_KEY, không trả cho client; hash lưu để nhận diện secret, không thay thế HMAC.
4. QR chứa URL xác nhận và fragment `#code=UUID.slot.HMAC`. HMAC gắn cả event_id. Slot 15 giây, server nhận slot hiện tại và slot liền trước để chịu độ trễ. Ảnh cũ chỉ sống dưới khoảng 30 giây và luôn bị chặn bởi hạn tuyệt đối của phiên.
5. Sinh viên đăng nhập quét bằng camera điện thoại, xem đúng tài khoản rồi POST có CSRF. GET không ghi nhận. Server chỉ lấy user từ auth, không dùng user_id gửi lên.
6. Server kiểm tra phiên đúng sự kiện/chưa thu hồi/now < expires_at, chữ ký, slot, user hoạt động/role student và đăng ký confirmed. Quét ở đúng expires_at hoặc sau đó bị từ chối. Kiểm tra lại hạn sau khi chờ khóa đăng ký.
7. Ghi event_completions, cập nhật trạng thái và audit cùng transaction. Lượt lặp trả kết quả đã ghi nhận; không cộng điểm tự động.
8. Approver/manager/admin duyệt bằng chứng hợp lệ, mức đề xuất và phần trần còn lại. Mọi điều chỉnh cần lý do/audit. Một lỗi ở hồ sơ sau trong batch không rollback các hồ sơ đã duyệt trước đó; batch xử lý transaction từng sinh viên và có thể chạy lại an toàn.

**Chọn MySQL làm nguồn sự thật** cho bản này: dữ liệu phiên cần bền vững, gắn snapshot, audit và thu hồi tức thời. Hết hạn được kiểm tra tại mỗi request nên không cần cron cập nhật status mới khóa được mã. Redis phù hợp làm cache chia sẻ, throttle, queue hoặc tối ưu đọc sau khi đo tải; không dùng cache có thể bị eviction làm bằng chứng duy nhất. Không cache phiên active theo cách trì hoãn việc thu hồi.

Token đặt ở fragment nhằm tránh ghi vào access log URL và xóa fragment khi trang nạp. Không ghi raw token vào audit. Vẫn cần HTTPS, tránh script bên thứ ba trên trang xác nhận và không bật debug/request-body logging khi vận hành.

## 4. Chống quét hộ: giới hạn thực tế

Mã ký/đổi nhanh giúp chống sửa mã và giảm dùng lại ảnh cũ, **không ngăn người trong hội trường chuyển ngay ảnh/mã còn hiệu lực cho người ngoài**. Mã dùng chung không thể one-time globally vì nhiều sinh viên cùng dùng; tính một lần nằm ở bản ghi từng sinh viên.

BTC nên giám sát khu vực quét, đối chiếu thẻ/MSSV ngẫu nhiên, giữ scan dự phòng; nếu yêu cầu xác minh mạnh hơn, có thể bổ sung nhân sự quét thẻ từng người tại lối ra hoặc danh sách xác nhận bởi BTC. IP/Wi-Fi/GPS là tín hiệu hỗ trợ, không phải bằng chứng tuyệt đối. Điểm danh cuối buổi chỉ xác nhận cuối buổi, không chứng minh đủ toàn bộ thời lượng. Online cần quy trình đối chiếu tham dự từ nền tảng riêng; bản hiện tại không tự đọc thời gian tham gia Meet/Teams.

## 5. Concurrency và khả năng mở rộng

- Khi mở/đóng dùng exclusive event lock. Khi quét dùng shared event/session/user lock và exclusive **đăng ký của sinh viên đó**, nên các sinh viên khác có thể ghi đồng thời; thu hồi/hủy đồng bộ với request đang chạy.
- Unique event/user và registration là hàng rào cuối, transaction retry tối đa 3 khi deadlock. Server phải dùng MySQL/InnoDB, không dựa SQLite để kết luận khóa hàng.
- Khi duyệt/điều chỉnh điểm khóa user trước, kể cả khác event; cùng sinh viên sẽ tuần tự hóa để không vượt tổng trần. Không gọi SMTP hay dịch vụ ngoài trong transaction.
- Index point_cap_lookup phục vụ tổng điểm, FK/unique phục vụ tra đăng ký/completion. Review phân trang 100; duyệt 200; giấy 500; CSV import 1.000; export chunk 500. Trang thống kê toàn trường vẫn cần đo execution plan/tải thực tế khi tăng dữ liệu.
- Throttle quét theo user thay vì IP chung toàn hội trường. Khi chạy nhiều instance cần Redis/shared limiter/session, cùng key và đồng bộ đồng hồ. Bản demo mặc định file cache/session phù hợp một máy.

Đã thử 120 tiến trình gọi service theo nhóm 20 worker trên MariaDB, dữ liệu sau test đúng; đây **không phải** thử HTTP hàng trăm concurrent hoặc 40.000 user. Muốn kết luận cần tạo tập dữ liệu đại diện, dùng k6/JMeter với phiên/CSRF thực, thử tải tăng dần, đo p95/p99, HTTP lỗi, connection pool, CPU/RAM, lock waits/deadlocks và đối chiếu hậu điều kiện DB. 40.000 hồ sơ không đồng nghĩa 40.000 request cùng lúc. Tối ưu cache danh sách, queue email/export lớn, PHP-FPM và cấu hình MySQL theo kết quả đo, không chỉ tăng TTL QR.

## 6. Địa điểm và tên hệ thống

Enum `app/Enums/EventVenue.php`: Hội trường C; Tầng 3 thư viện; Sân trường; Phòng hội thảo; Online (Google Meet/MS Teams). Dữ liệu demo được seed theo danh sách này, không dùng các nhãn phòng giả cũ. Đây không phải xác nhận danh mục phòng chính thức của trường.

| Tên | Slogan gợi ý |
|---|---|
| **HUIT EventPass** (đã áp dụng) | Mỗi hoạt động, một dấu ấn |
| HUIT Checkin | Tham gia rõ ràng, ghi nhận minh bạch |
| HUIT Life | Kết nối trải nghiệm, ghi dấu trưởng thành |
| HUIT Connect | Kết nối hôm nay, đồng hành ngày mai |
| HUIT Activity | Hoạt động ý nghĩa, hành trình đáng nhớ |

## 7. Phạm vi còn cần nghiệm thu

Xem TEST_RESULTS và hướng dẫn nâng cấp. Bản này không tích hợp cổng điểm HUIT, SSO, email OTP/khôi phục mật khẩu hoàn chỉnh, nền tảng họp online, XLSX/PDF, antivirus file, backup/HA hay hệ thống tải 40.000 concurrent. Email đang log, không phải SMTP đã cấu hình. File có giới hạn MIME/kích thước, private path và download có quyền; nếu vận hành thật nên thêm kiểm tra nội dung/malware theo hạ tầng trường. Cần xác nhận quy chế điểm, phạm vi dữ liệu, quyền BTC, UAT và tải thực tế trước khi đưa vào vận hành.

Tài liệu kỹ thuật tham chiếu: Laravel Queries/Pessimistic Locking https://laravel.com/docs/12.x/queries ; Laravel Encryption https://laravel.com/docs/12.x/encryption ; OWASP Session Management https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html ; BaconQrCode https://github.com/Bacon/BaconQrCode .
