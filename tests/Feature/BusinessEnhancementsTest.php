<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Criteria;
use App\Models\Event;
use App\Models\EventStaff;
use App\Models\Semester;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_displays_criteria_progress_and_recommendations(): void
    {
        $sem = Semester::create([
            'code' => '2026_HK1',
            'name' => 'Học kỳ 1 2026',
            'starts_at' => '2026-08-01',
            'ends_at' => '2027-01-31',
            'is_active' => true,
        ]);

        $cat = Category::create(['name' => 'Học thuật & AI']);

        $crit = Criteria::create([
            'code' => 'TC1.1',
            'name' => 'Ý thức học tập & NCKH',
            'max_points' => 20,
            'rule_version' => 'QĐ-HUIT-2026',
        ]);

        $student = User::create([
            'name' => 'Nguyễn Sinh Viên',
            'email' => 'sv@test.com',
            'student_code' => '2001239999',
            'password' => bcrypt('password'),
            'role' => 'student',
            'is_active' => true,
        ]);

        // Sự kiện chưa đăng ký để test gợi ý thông minh
        Event::create([
            'title' => 'Chuyên đề Tọa đàm AI',
            'description' => 'Mô tả chuyên đề AI',
            'category_id' => $cat->id,
            'created_by' => $student->id,
            'semester_id' => $sem->id,
            'criteria_id' => $crit->id,
            'venue' => 'Hội trường C',
            'capacity' => 100,
            'proposed_points' => 5,
            'status' => 'published',
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDays(2),
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(2),
        ]);

        $res = $this->actingAs($student)->get('/my-points');
        $res->assertOk();
        $res->assertSee('Bảng tiến độ điểm rèn luyện sinh viên');
        $res->assertSee('TC1.1');
        $res->assertSee('Gợi ý hoạt động để tích lũy tiêu chí còn thiếu');
        $res->assertSee('Chuyên đề Tọa đàm AI');
    }

    public function test_staff_workspace_accessible_by_assigned_staff(): void
    {
        $cat = Category::create(['name' => 'Kỹ năng']);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin_test@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Nhân sự Scanner',
            'email' => 'scanner_test@test.com',
            'student_code' => '2001238888',
            'password' => bcrypt('password'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $event = Event::create([
            'title' => 'Workshop CV',
            'description' => 'Mô tả workshop CV',
            'category_id' => $cat->id,
            'created_by' => $admin->id,
            'venue' => 'Phòng hội thảo',
            'capacity' => 50,
            'status' => 'published',
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDay(),
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(2),
        ]);

        EventStaff::create([
            'event_id' => $event->id,
            'user_id' => $staff->id,
            'role' => 'scanner',
            'assigned_by' => $admin->id,
        ]);

        $res = $this->actingAs($staff)->get('/staff/events');
        $res->assertOk();
        $res->assertSee('Sự kiện tôi phụ trách');
        $res->assertSee('Workshop CV');
        $res->assertSee('Scanner · Điểm danh');
    }

    public function test_manager_can_finalize_event(): void
    {
        $cat = Category::create(['name' => 'Tình nguyện']);
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager_test@test.com',
            'student_code' => '2001237777',
            'password' => bcrypt('password'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $event = Event::create([
            'title' => 'Hiến máu tình nguyện',
            'description' => 'Mô tả hiến máu',
            'category_id' => $cat->id,
            'created_by' => $manager->id,
            'venue' => 'Sân trường',
            'capacity' => 100,
            'status' => 'published',
            'opens_at' => now()->subDays(3),
            'closes_at' => now()->subDay(),
            'starts_at' => now()->subHours(5),
            'ends_at' => now()->subHour(),
        ]);

        EventStaff::create([
            'event_id' => $event->id,
            'user_id' => $manager->id,
            'role' => 'manager',
            'assigned_by' => $manager->id,
        ]);

        $res = $this->actingAs($manager)->post('/events/'.$event->id.'/finalize');
        $res->assertRedirect();
        $this->assertTrue($event->fresh()->is_finalized);
    }

    public function test_notification_hub_mark_read_and_read_all(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'notif_test@test.com',
            'student_code' => '2001236666',
            'password' => bcrypt('password'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $notif1 = NotificationService::send($user, 'Thông báo 1', 'Nội dung 1', '/link1', 'info', 'bell');
        $notif2 = NotificationService::send($user, 'Thông báo 2', 'Nội dung 2', '/link2', 'point', 'star');

        $this->assertSame(2, NotificationService::getUnreadCount($user));

        // Đọc 1 thông báo
        $res = $this->actingAs($user)->post('/notifications/'.$notif1->id.'/read');
        $res->assertRedirect('/link1');
        $this->assertTrue($notif1->fresh()->is_read);
        $this->assertSame(1, NotificationService::getUnreadCount($user));

        // Đọc tất cả
        $res2 = $this->actingAs($user)->post('/notifications/read-all');
        $res2->assertRedirect();
        $this->assertSame(0, NotificationService::getUnreadCount($user));
    }
}
