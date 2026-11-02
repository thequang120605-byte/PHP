<?php

namespace Database\Seeders;

use App\Enums\EventVenue;
use App\Models\Category;
use App\Models\Criteria;
use App\Models\Event;
use App\Models\EventStaff;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@huit.test'], [
            'name' => 'Quản trị HUIT EventPass',
            'password' => 'Admin@12345',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $student = User::firstOrCreate(['email' => 'sinhvien@huit.test'], [
            'name' => 'Nguyễn Thế Quang',
            'student_code' => '2001234567',
            'class_name' => '14DHTH_C5',
            'password' => 'Student@12345',
            'role' => 'student',
            'is_active' => true,
        ]);

        $scanner = User::firstOrCreate(['email' => 'tv3_scanner@huit.test'], [
            'name' => 'Nhân sự điểm danh',
            'student_code' => '2001230003',
            'class_name' => 'BTC',
            'password' => 'Student@12345',
            'role' => 'student',
            'is_active' => true,
        ]);

        $approver = User::firstOrCreate(['email' => 'tv4_approver@huit.test'], [
            'name' => 'Người duyệt điểm',
            'student_code' => '2001230004',
            'class_name' => 'BTC',
            'password' => 'Student@12345',
            'role' => 'student',
            'is_active' => true,
        ]);

        $sem = Semester::firstOrCreate(['code' => '2026_HK1'], [
            'name' => 'Học kỳ 1 (Năm học 2026 - 2027)',
            'starts_at' => '2026-08-01',
            'ends_at' => '2027-01-31',
            'is_active' => true,
        ]);

        $critHocThuat = Criteria::firstOrCreate(['code' => 'TC1.1'], [
            'name' => 'Ý thức học tập, nghiên cứu khoa học và chuyên đề học thuật',
            'max_points' => 20,
            'rule_version' => 'QĐ-HUIT-2026',
            'description' => 'Tham gia đầy đủ các buổi hội thảo khoa học, tọa đàm công nghệ, báo cáo chuyên đề cấp trường/khoa.',
        ]);

        $critKyNang = Criteria::firstOrCreate(['code' => 'TC2.1'], [
            'name' => 'Tham gia các hoạt động rèn luyện kỹ năng mềm và hướng nghiệp',
            'max_points' => 25,
            'rule_version' => 'QĐ-HUIT-2026',
            'description' => 'Kỹ năng làm việc nhóm, phỏng vấn tuyển dụng, khởi nghiệp đổi mới sáng tạo.',
        ]);

        $critTinhNguyen = Criteria::firstOrCreate(['code' => 'TC3.1'], [
            'name' => 'Hoạt động vì cộng đồng, tình nguyện và bảo vệ môi trường',
            'max_points' => 20,
            'rule_version' => 'QĐ-HUIT-2026',
            'description' => 'Hiến máu tình nguyện, mùa hè xanh, tiếp sức mùa thi, công tác an sinh xã hội.',
        ]);

        $critTheThao = Criteria::firstOrCreate(['code' => 'TC4.1'], [
            'name' => 'Hoạt động thể dục thể thao, văn hóa nghệ thuật sinh viên',
            'max_points' => 20,
            'rule_version' => 'QĐ-HUIT-2026',
            'description' => 'Hội thao sinh viên, giải chạy, thi đấu thể thao và hội diễn văn nghệ HUIT.',
        ]);

        $catHocThuat = Category::firstOrCreate(['name' => 'Học thuật & AI']);
        $catKyNang = Category::firstOrCreate(['name' => 'Kỹ năng & Khởi nghiệp']);
        $catTinhNguyen = Category::firstOrCreate(['name' => 'Tình nguyện & Cộng đồng']);
        $catTheThao = Category::firstOrCreate(['name' => 'Văn hóa & Thể thao']);

        $sampleEvents = [
            [
                'title' => 'Chuyên đề: Ứng dụng Trí tuệ Nhân tạo & LLMs trong Kỹ thuật Phần mềm',
                'category_id' => $catHocThuat->id,
                'criteria_id' => $critHocThuat->id,
                'venue' => EventVenue::Hall->value,
                'capacity' => 180,
                'proposed_points' => 5,
                'description' => "Chuyên đề đặc biệt dành cho sinh viên Khoa CNTT với sự tham gia của các chuyên gia đầu ngành về AI và Machine Learning.\n\nNội dung chính:\n1. Xu hướng phát triển của Agentic AI và mô hình ngôn ngữ lớn.\n2. Thực hành xây dựng ứng dụng với Gemini API và Python.\n3. Định hướng nghề nghiệp ngành AI Engineer tại các tập đoàn công nghệ lớn.",
                'opens_at' => now()->subDays(3),
                'closes_at' => now()->addDays(4),
                'starts_at' => now()->addDays(5)->setTime(8, 30),
                'ends_at' => now()->addDays(5)->setTime(11, 30),
                'is_ended' => false,
            ],
            [
                'title' => 'Workshop: Kỹ năng viết CV Chuẩn ATS và Chinh phục Nhà tuyển dụng',
                'category_id' => $catKyNang->id,
                'criteria_id' => $critKyNang->id,
                'venue' => EventVenue::Conference->value,
                'capacity' => 250,
                'proposed_points' => 3,
                'description' => "Chuỗi workshop hướng nghiệp chuẩn bị hành trang vững chắc cho sinh viên năm 3, năm 4 trước kỳ thực tập doanh nghiệp.\n\nSinh viên được sửa CV 1-1 trực tiếp và tham gia phỏng vấn thử nghiệm (Mock Interview) với HR Manager đến từ các công ty đối tác HUIT.",
                'opens_at' => now()->subDays(2),
                'closes_at' => now()->addDays(2),
                'starts_at' => now()->addDays(3)->setTime(13, 30),
                'ends_at' => now()->addDays(3)->setTime(16, 30),
                'is_ended' => false,
            ],
            [
                'title' => 'Ngày hội Hiến máu Nhân đạo HUIT 2026 - Giọt hồng sẻ chia',
                'category_id' => $catTinhNguyen->id,
                'criteria_id' => $critTinhNguyen->id,
                'venue' => EventVenue::Yard->value,
                'capacity' => 300,
                'proposed_points' => 5,
                'description' => "Hoạt động hiến máu tình nguyện thường niên do Đoàn Thanh niên - Hội Sinh viên HUIT phối hợp cùng Bệnh viện Truyền máu Huyết học TP.HCM tổ chức.\n\nMỗi giọt máu cho đi - một cuộc đời ở lại. Sinh viên tham gia được nhận quà lưu niệm, giấy chứng nhận hiến máu và cộng điểm rèn luyện tiêu chí tình nguyện.",
                'opens_at' => now()->subDays(5),
                'closes_at' => now()->addDays(1),
                'starts_at' => now()->addDays(2)->setTime(7, 0),
                'ends_at' => now()->addDays(2)->setTime(11, 30),
                'is_ended' => false,
            ],
            [
                'title' => 'Hội thao Sinh viên HUIT 2026 - Khai mạc Giải Bóng đá & Cầu lông',
                'category_id' => $catTheThao->id,
                'criteria_id' => $critTheThao->id,
                'venue' => EventVenue::Library->value,
                'capacity' => 400,
                'proposed_points' => 4,
                'description' => "Lễ khai mạc hội thao sinh viên toàn trường chào mừng năm học mới. Quy tụ hơn 30 đội bóng đến từ tất cả các khoa viện.\n\nSinh viên tham gia cổ vũ và đồng diễn được ghi nhận điểm danh qua hệ thống HUIT EventPass tại sảnh sự kiện.",
                'opens_at' => now()->subDays(1),
                'closes_at' => now()->addDays(3),
                'starts_at' => now()->addDays(4)->setTime(7, 30),
                'ends_at' => now()->addDays(4)->setTime(10, 30),
                'is_ended' => false,
            ],
            [
                'title' => '[ĐÃ KẾT THÚC DEMO] Tọa đàm Nghiên cứu Khoa học và Công bố Quốc tế',
                'category_id' => $catHocThuat->id,
                'criteria_id' => $critHocThuat->id,
                'venue' => EventVenue::Hall->value,
                'capacity' => 120,
                'proposed_points' => 4,
                'description' => "Hoạt động demo đã kết thúc để kiểm thử quy trình: Mở QR điểm danh cuối sự kiện -> Đối soát tham dự -> Duyệt điểm rèn luyện -> Xử lý khiếu nại sai sót.",
                'opens_at' => now()->subDays(4),
                'closes_at' => now()->subHours(4),
                'starts_at' => now()->subHours(3),
                'ends_at' => now()->subMinute(),
                'is_ended' => true,
            ],
        ];

        foreach ($sampleEvents as $data) {
            $isEnded = $data['is_ended'];
            unset($data['is_ended']);

            $data['created_by'] = $admin->id;
            $data['semester_id'] = $sem->id;
            $data['status'] = 'published';
            $data['attendance_rule'] = 'end_only';
            $data['min_minutes_between'] = 0;
            $data['dispute_closes_at'] = now()->addDays(7);

            $e = Event::firstOrCreate(['title' => $data['title']], $data);

            foreach ([[$scanner, 'scanner'], [$approver, 'approver']] as [$u, $role]) {
                EventStaff::firstOrCreate(['event_id' => $e->id, 'user_id' => $u->id, 'role' => $role], [
                    'assigned_by' => $admin->id,
                ]);
            }

            if ($isEnded) {
                Registration::firstOrCreate(['event_id' => $e->id, 'user_id' => $student->id], [
                    'reference' => (string) Str::uuid(),
                    'status' => 'confirmed',
                    'attendance_status' => 'registered',
                    'submitted_at' => now()->subDay(),
                    'snapshot' => [
                        'name' => $student->name,
                        'student_code' => $student->student_code,
                        'class_name' => $student->class_name,
                        'email' => $student->email,
                        'source' => 'demo_seed',
                        'event_title' => $e->title,
                        'opens_at' => $e->opens_at->toIso8601String(),
                        'closes_at' => $e->closes_at->toIso8601String(),
                        'proposed_points' => $e->proposed_points,
                    ],
                ]);
            }
        }
    }
}
