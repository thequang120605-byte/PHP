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
        $catDoanhNghiep = Category::firstOrCreate(['name' => 'Hướng nghiệp & Doanh nghiệp']);

        $sampleEvents = [
            [
                'title' => 'Chuyên đề: Ứng dụng Trí tuệ Nhân tạo & LLMs trong Kỹ thuật Phần mềm',
                'category_id' => $catHocThuat->id,
                'criteria_id' => $critHocThuat->id,
                'venue' => EventVenue::Hall->value,
                'capacity' => 180,
                'proposed_points' => 5,
                'description' => "Chuyên đề đặc biệt dành cho sinh viên Khoa CNTT với sự tham gia của các chuyên gia đầu ngành về AI và Machine Learning.\n\nNội dung chính:\n1. Xu hướng phát triển của Agentic AI và mô hình ngôn ngữ lớn (LLMs).\n2. Thực hành xây dựng ứng dụng với Gemini API và Python.\n3. Định hướng nghề nghiệp ngành AI Engineer tại các tập đoàn công nghệ lớn.",
                'opens_at' => now()->subDays(3),
                'closes_at' => now()->addDays(4),
                'starts_at' => now()->addDays(5)->setTime(8, 30),
                'ends_at' => now()->addDays(5)->setTime(11, 30),
                'is_ended' => false,
            ],
            [
                'title' => 'Hackathon HUIT 2026: Sáng tạo Ứng dụng Generative AI & Cloud',
                'category_id' => $catHocThuat->id,
                'criteria_id' => $critHocThuat->id,
                'venue' => EventVenue::Conference->value,
                'capacity' => 120,
                'proposed_points' => 8,
                'description' => "Cuộc thi lập trình 24 giờ liên tục dành cho các đội thi sinh viên đam mê công nghệ. Giải thưởng lên tới 50 triệu đồng cùng cơ hội thực tập tại các đối tác công nghệ hàng đầu.",
                'opens_at' => now()->subDays(2),
                'closes_at' => now()->addDays(6),
                'starts_at' => now()->addDays(7)->setTime(8, 0),
                'ends_at' => now()->addDays(8)->setTime(12, 0),
                'is_ended' => false,
            ],
            [
                'title' => 'Talkshow: Xu hướng An toàn Thông tin & Tác chiến Không gian Mạng',
                'category_id' => $catHocThuat->id,
                'criteria_id' => $critHocThuat->id,
                'venue' => EventVenue::Online->value,
                'capacity' => 300,
                'proposed_points' => 4,
                'description' => "Chia sẻ từ các chuyên gia bảo mật và chuyên viên ứng cứu sự cố không gian mạng. Giúp sinh viên nắm vững các kỹ thuật phòng thủ, ethical hacking và chứng chỉ bảo mật quốc tế.",
                'opens_at' => now()->subDays(1),
                'closes_at' => now()->addDays(3),
                'starts_at' => now()->addDays(4)->setTime(19, 0),
                'ends_at' => now()->addDays(4)->setTime(21, 30),
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
                'title' => 'HUIT Startup Demo Day: Vườn ươm Ý tưởng Đổi mới Sáng tạo Sinh viên',
                'category_id' => $catKyNang->id,
                'criteria_id' => $critKyNang->id,
                'venue' => EventVenue::Hall->value,
                'capacity' => 200,
                'proposed_points' => 6,
                'description' => "Vòng chung kết pitching các dự án khởi nghiệp sáng tạo của sinh viên HUIT trước hội đồng các nhà đầu tư thiên thần và quỹ khởi nghiệp.",
                'opens_at' => now()->subDays(3),
                'closes_at' => now()->addDays(5),
                'starts_at' => now()->addDays(6)->setTime(8, 0),
                'ends_at' => now()->addDays(6)->setTime(12, 0),
                'is_ended' => false,
            ],
            [
                'title' => 'Masterclass: Nghệ thuật Thuyết trình & Giao tiếp Đỉnh cao trước Công chúng',
                'category_id' => $catKyNang->id,
                'criteria_id' => $critKyNang->id,
                'venue' => EventVenue::Library->value,
                'capacity' => 150,
                'proposed_points' => 3,
                'description' => "Khai phá kỹ năng làm chủ sân khấu, kiểm soát giọng nói, ngôn ngữ cơ thể và kỹ thuật thiết kế slide thuyết trình truyền cảm hứng.",
                'opens_at' => now()->subDays(1),
                'closes_at' => now()->addDays(4),
                'starts_at' => now()->addDays(5)->setTime(14, 0),
                'ends_at' => now()->addDays(5)->setTime(17, 0),
                'is_ended' => false,
            ],
            [
                'title' => 'Ngày hội Hiến máu Nhân đạo HUIT 2026 - Giọt hồng sẻ chia',
                'category_id' => $catTinhNguyen->id,
                'criteria_id' => $critTinhNguyen->id,
                'venue' => EventVenue::Yard->value,
                'capacity' => 350,
                'proposed_points' => 5,
                'description' => "Hoạt động hiến máu tình nguyện thường niên do Đoàn Thanh niên - Hội Sinh viên HUIT phối hợp cùng Bệnh viện Truyền máu Huyết học TP.HCM tổ chức.\n\nMỗi giọt máu cho đi - một cuộc đời ở lại. Sinh viên tham gia được nhận quà lưu niệm, giấy chứng nhận hiến máu và cộng điểm rèn luyện tiêu chí tình nguyện.",
                'opens_at' => now()->subDays(5),
                'closes_at' => now()->addDays(1),
                'starts_at' => now()->addDays(2)->setTime(7, 0),
                'ends_at' => now()->addDays(2)->setTime(11, 30),
                'is_ended' => false,
            ],
            [
                'title' => 'Chiến dịch Tình nguyện Mùa Hè Xanh & Tiếp sức Mùa thi HUIT 2026',
                'category_id' => $catTinhNguyen->id,
                'criteria_id' => $critTinhNguyen->id,
                'venue' => EventVenue::Yard->value,
                'capacity' => 500,
                'proposed_points' => 10,
                'description' => "Lễ ra quân các đội hình tình nguyện Mùa Hè Xanh tại các mặt trận trọng điểm. Gắn kết tinh thần cống hiến, rèn luyện bản lĩnh và trưởng thành của tuổi trẻ HUIT.",
                'opens_at' => now()->subDays(4),
                'closes_at' => now()->addDays(8),
                'starts_at' => now()->addDays(9)->setTime(6, 30),
                'ends_at' => now()->addDays(9)->setTime(11, 0),
                'is_ended' => false,
            ],
            [
                'title' => 'Ngày Chủ nhật Xanh: Thu gom Rác thải Điện tử & Vì Môi trường Học đường',
                'category_id' => $catTinhNguyen->id,
                'criteria_id' => $critTinhNguyen->id,
                'venue' => EventVenue::Yard->value,
                'capacity' => 200,
                'proposed_points' => 4,
                'description' => "Chiến dịch xanh đổi pin cũ và rác thải điện tử lấy cây xanh trang trí bàn học, lan tỏa thông điệp sống xanh bền vững trong khuôn viên trường.",
                'opens_at' => now()->subDays(2),
                'closes_at' => now()->addDays(3),
                'starts_at' => now()->addDays(4)->setTime(8, 0),
                'ends_at' => now()->addDays(4)->setTime(11, 0),
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
                'title' => 'Giải chạy Việt dã HUIT Run 2026: Bước chạy Thanh niên Khỏe để Lập nghiệp',
                'category_id' => $catTheThao->id,
                'criteria_id' => $critTheThao->id,
                'venue' => EventVenue::Yard->value,
                'capacity' => 600,
                'proposed_points' => 5,
                'description' => "Giải chạy thường niên cự ly 5km và 10km quanh khuôn viên Tân Phú, nâng cao thể lực và sức khỏe cho toàn thể đoàn viên, sinh viên HUIT.",
                'opens_at' => now()->subDays(3),
                'closes_at' => now()->addDays(4),
                'starts_at' => now()->addDays(5)->setTime(6, 0),
                'ends_at' => now()->addDays(5)->setTime(9, 30),
                'is_ended' => false,
            ],
            [
                'title' => 'Đêm hội Âm nhạc Sinh viên & Lễ Tuyên dương Sinh viên 5 Tốt HUIT',
                'category_id' => $catTheThao->id,
                'criteria_id' => $critTheThao->id,
                'venue' => EventVenue::Hall->value,
                'capacity' => 500,
                'proposed_points' => 4,
                'description' => "Đêm ca nhạc giao lưu cùng các nghệ sĩ khách mời và lễ vinh danh các cá nhân, tập thể đạt danh hiệu Sinh viên 5 Tốt cấp Trường năm học 2025-2026.",
                'opens_at' => now()->subDays(2),
                'closes_at' => now()->addDays(7),
                'starts_at' => now()->addDays(8)->setTime(18, 0),
                'ends_at' => now()->addDays(8)->setTime(21, 30),
                'is_ended' => false,
            ],
            [
                'title' => 'Ngày hội Việc làm & Kết nối Doanh nghiệp HUIT Job Fair 2026',
                'category_id' => $catDoanhNghiep->id,
                'criteria_id' => $critKyNang->id,
                'venue' => EventVenue::Yard->value,
                'capacity' => 800,
                'proposed_points' => 5,
                'description' => "Quy tụ hơn 60 tập đoàn, doanh nghiệp trong và ngoài nước tuyển dụng trực tiếp hàng ngàn vị trí thực tập sinh và kỹ sư/cử nhân mới tốt nghiệp.",
                'opens_at' => now()->subDays(3),
                'closes_at' => now()->addDays(5),
                'starts_at' => now()->addDays(6)->setTime(8, 0),
                'ends_at' => now()->addDays(6)->setTime(16, 0),
                'is_ended' => false,
            ],
            [
                'title' => '[ĐÃ KẾT THÚC DEMO] Tọa đàm Nghiên cứu Khoa học và Công bố Quốc tế',
                'category_id' => $catHocThuat->id,
                'criteria_id' => $critHocThuat->id,
                'venue' => EventVenue::Online->value,
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

            if ($isEnded && !app()->runningUnitTests()) {
                $reg = Registration::firstOrCreate(['event_id' => $e->id, 'user_id' => $student->id], [
                    'reference' => (string) Str::uuid(),
                    'status' => 'confirmed',
                    'attendance_status' => 'attended_complete',
                    'submitted_at' => now()->subDays(2),
                    'completed_at' => now()->subMinute(),
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

                // Tạo bằng chứng hoàn thành (EventCompletion)
                \App\Models\EventCompletion::firstOrCreate(['event_id' => $e->id, 'user_id' => $student->id], [
                    'registration_id' => $reg->id,
                    'method' => 'qr',
                    'completed_at' => now()->subMinute(),
                    'recorded_by' => $scanner->id,
                    'rule_snapshot' => ['rule' => 'end_only'],
                ]);

                // Ghi nhận điểm rèn luyện đã duyệt mẫu cho sinh viên
                \App\Models\StudentPoint::firstOrCreate(['event_id' => $e->id, 'student_id' => $student->id], [
                    'semester_id' => $sem->id,
                    'criteria_id' => $e->criteria_id,
                    'points' => $e->proposed_points,
                    'status' => 'approved',
                    'approved_by' => $approver->id,
                    'approved_at' => now()->subSeconds(30),
                    'rule_snapshot' => [
                        'criteria_code' => $e->criteria->code,
                        'criteria_name' => $e->criteria->name,
                        'criteria_max' => $e->criteria->max_points,
                        'proposed_points' => $e->proposed_points,
                        'rule_version' => $e->criteria->rule_version,
                    ],
                    'notes' => 'Duyệt điểm rèn luyện chính thức đợt xét duyệt demo.',
                ]);
            }
        }

        // Tạo thông báo mẫu cho sinh viên
        \App\Models\AppNotification::firstOrCreate([
            'user_id' => $student->id,
            'title' => 'Chào mừng bạn đến với HUIT EventPass!',
        ], [
            'message' => 'Hệ thống quản lý hoạt động & điểm rèn luyện số của Trường Đại học Công Thương TP.HCM. Bắt đầu khám phá và đăng ký sự kiện ngay hôm nay.',
            'type' => 'info',
            'icon' => 'bell',
            'link' => '/',
            'is_read' => false,
        ]);

        \App\Models\AppNotification::firstOrCreate([
            'user_id' => $student->id,
            'title' => 'Vé giữ chỗ: Chuyên đề AI & LLMs',
        ], [
            'message' => 'Mã vé điện tử của bạn đã được kích hoạt. Hãy quét mã QR chung tại hội trường khi kết thúc để được ghi nhận ĐRL.',
            'type' => 'registration',
            'icon' => 'ticket',
            'link' => '/my-registrations',
            'is_read' => false,
        ]);
    }
}
