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
        $admin = User::firstOrCreate(['email' => 'admin@huit.test'], ['name' => 'Quản trị HUIT EventPass', 'password' => 'Admin@12345', 'role' => 'admin', 'is_active' => true]);
        $student = User::firstOrCreate(['email' => 'sinhvien@huit.test'], ['name' => 'Nguyễn Thế Quang', 'student_code' => '2001234567', 'class_name' => '14DHTH_C5', 'password' => 'Student@12345', 'role' => 'student', 'is_active' => true]);
        $scanner = User::firstOrCreate(['email' => 'tv3_scanner@huit.test'], ['name' => 'Nhân sự điểm danh', 'student_code' => '2001230003', 'class_name' => 'BTC', 'password' => 'Student@12345', 'role' => 'student', 'is_active' => true]);
        $approver = User::firstOrCreate(['email' => 'tv4_approver@huit.test'], ['name' => 'Người duyệt điểm', 'student_code' => '2001230004', 'class_name' => 'BTC', 'password' => 'Student@12345', 'role' => 'student', 'is_active' => true]);
        $sem = Semester::firstOrCreate(['code' => 'DEMO_2026_HK1'], ['name' => 'Học kỳ demo 2026 2027', 'starts_at' => '2026-08-01', 'ends_at' => '2027-01-31', 'is_active' => true]);
        // Demonstration rules are not asserted to be official HUIT scoring criteria.
        $crit = Criteria::firstOrCreate(['code' => 'DEMO_ACTIVITY'], ['name' => 'Hoạt động học thuật và kỹ năng demo', 'max_points' => 20, 'rule_version' => 'DEMO-v1', 'description' => 'Quy tắc minh họa đồ án. Thay bằng văn bản và phụ lục đã được xác nhận khi áp dụng thực tế.']);
        foreach (EventVenue::cases() as $i => $venue) {
            $cat = Category::firstOrCreate(['name' => $i % 2 ? 'Kỹ năng' : 'Học thuật']);
            $ended = $i === 0;
            $e = Event::firstOrCreate(['title' => ($ended ? '[DEMO ĐÃ KẾT THÚC] Chuyên đề AI' : 'Hoạt động trải nghiệm '.($i + 1))], ['category_id' => $cat->id, 'created_by' => $admin->id, 'semester_id' => $sem->id, 'criteria_id' => $crit->id, 'description' => 'Đăng ký giữ chỗ. Cuối sự kiện BTC mở QR chung đúng 10 phút. Xác nhận tham dự sau đó chờ duyệt điểm đề xuất.', 'venue' => $venue->value, 'capacity' => 100, 'proposed_points' => 3, 'status' => 'published', 'attendance_rule' => 'end_only', 'min_minutes_between' => 0, 'opens_at' => now()->subDays(2), 'closes_at' => $ended ? now()->subHours(3) : now()->addDays(2), 'starts_at' => $ended ? now()->subHours(2) : now()->addDays(3), 'ends_at' => $ended ? now()->subMinute() : now()->addDays(3)->addHours(2), 'dispute_closes_at' => now()->addDays(7)]);
            foreach ([[$scanner, 'scanner'], [$approver, 'approver']] as [$u,$role]) {
                EventStaff::firstOrCreate(['event_id' => $e->id, 'user_id' => $u->id, 'role' => $role], ['assigned_by' => $admin->id]);
            }
            if ($ended) {
                Registration::firstOrCreate(['event_id' => $e->id, 'user_id' => $student->id], ['reference' => (string) Str::uuid(), 'status' => 'confirmed', 'attendance_status' => 'registered', 'submitted_at' => now()->subDay(), 'snapshot' => ['name' => $student->name, 'student_code' => $student->student_code, 'class_name' => $student->class_name, 'email' => $student->email, 'source' => 'demo_seed', 'event_title' => $e->title, 'opens_at' => $e->opens_at->toIso8601String(), 'closes_at' => $e->closes_at->toIso8601String(), 'proposed_points' => 3]]);
            }
        }
    }
}
