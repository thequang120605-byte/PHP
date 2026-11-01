# Nâng cấp có giữ lịch sử

## Trước khi chạy

Sao lưu database, `.env`, APP_KEY đang dùng và toàn bộ storage. Thử trên bản sao MySQL trước. Thực hiện trong cửa sổ bảo trì: DDL MySQL có thể khóa bảng và không rollback toàn bộ như transaction nghiệp vụ. Không dùng `migrate:fresh`, không chạy lại seed trên CSDL vận hành nếu chưa xem dữ liệu demo.

1. Giữ nguyên `.env` và **APP_KEY cũ**; không chạy `key:generate` khi nâng cấp. Backup key cũng cần thiết để giải mã secret phiên đã tạo.
2. Copy code mới và vendor hoặc chạy `composer install` đúng lockfile. Giữ nguyên storage cũ, không lấy storage rỗng đè mất dữ liệu.
3. Chạy:

```bat
php artisan down
php artisan optimize:clear
php artisan migrate --force
php artisan evidence:privatize
php artisan storage:link
php artisan up
```

Chỉ `up` khi tất cả bước trước thành công và đã thử tải minh chứng qua route có xác thực. Nếu lỗi, giữ bảo trì để xử lý thay vì tiếp tục công bố.

## Minh chứng từng lưu public

**Bắt buộc chạy `evidence:privatize` nếu có scan/khiếu nại cũ.** Đổi route không tự làm mất các URL public cũ. Lệnh copy sang local private, đối chiếu SHA-256 rồi xóa bản public; nếu có xung đột nội dung thì dừng để kiểm tra. Giữ nguyên đường dẫn trong database. Lệnh chỉ xử lý file có tham chiếu trong bảng paper_attendances/disputes; cần tự kiểm tra và cách ly các file public mồ côi không còn tham chiếu. Nếu từng có CDN/cache công khai, xóa cache đó.

## Dữ liệu cũ và migration

- Migration bổ sung bảng phiên QR/bằng chứng, cột snapshot và unique constraint; không tự xóa điểm hoặc bản ghi điểm danh cũ.
- Nếu trùng `(student_id,event_id)` trong student_points: migration từ chối trước DDL. Xuất bản sao, đối chiếu audit/điều chỉnh, thống nhất bản ghi hợp lệ rồi chạy lại; không tự xóa chỉ để migration qua.
- Giờ vào/ra cũ không được mặc định coi là bằng chứng hoàn thành theo quy trình mới. Đối soát bằng scan đã xác minh khi cần. Điểm đã duyệt trước đây được giữ, không tự thu hồi; muốn tăng điểm cũ phải có bằng chứng hoàn thành.
- Luật điểm danh chuyển sang `end_only`. Các nhãn demo biết trước được đổi sang địa điểm yêu cầu; địa điểm lịch sử khác giữ lại để admin rà soát. Không ép tất cả sự kiện cũ về cùng một địa điểm.
- Có lịch sử phiên/bằng chứng/điểm thì khóa sửa học kỳ, tiêu chí, điểm đề xuất, giờ kết thúc và luật điểm danh của sự kiện. Hủy sự kiện vẫn làm mã hết hiệu lực ngay; điểm đã duyệt cần người phụ trách xem xét thu hồi với lý do, không tự xóa lịch sử.
- `migrate:rollback` không khôi phục nhãn địa điểm/luật cũ và sẽ loại bỏ bằng chứng mới. Phục hồi backup đồng bộ DB + storage + key nếu thật sự cần quay về bản cũ.

## Kiểm tra sau nâng cấp

Thử vai trò scanner/approver/student, thu hồi mã, mốc 10 phút, quét trùng, mở lại có lý do; đối chiếu số đăng ký và điểm trước/sau; thử tải file private bằng tài khoản đúng và sai quyền. Với HTTPS đặt `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`; web root trỏ vào public, không lộ thư mục dự án. Không dùng tài khoản root hoặc mật khẩu demo cho hệ thống vận hành.
