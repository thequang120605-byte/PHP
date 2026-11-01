<?php

namespace Tests\Feature;

use App\Enums\EventVenue;
use App\Models\AttendanceSession;
use App\Models\Category;
use App\Models\Criteria;
use App\Models\Event;
use App\Models\EventStaff;
use App\Models\PaperAttendance;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\StudentPoint;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\DisputeService;
use App\Services\PointService;
use App\Services\RegistrationService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EndAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'student'): User
    {
        return User::create(['name' => 'Sinh viên thử', 'email' => Str::uuid().'@test.com', 'password' => 'Student@12345', 'student_code' => (string) random_int(1000000000, 1999999999), 'class_name' => '14DHTH', 'role' => $role, 'is_active' => true]);
    }

    private function setupEvent(): array
    {
        $this->travelTo(now()->startOfSecond());
        $a = $this->user('admin');
        $u = $this->user();
        $cat = Category::create(['name' => 'Học thuật']);
        $sem = Semester::create(['code' => 'DEMO', 'name' => 'Demo', 'starts_at' => '2026-01-01', 'ends_at' => '2027-12-31', 'is_active' => true]);
        $crit = Criteria::create(['code' => 'DEMO', 'name' => 'Demo', 'max_points' => 5, 'rule_version' => 'demo-v1']);
        $e = Event::create(['title' => 'Chuyên đề AI', 'description' => 'Demo', 'venue' => 'Hội trường C', 'created_by' => $a->id, 'category_id' => $cat->id, 'semester_id' => $sem->id, 'criteria_id' => $crit->id, 'capacity' => 100, 'proposed_points' => 3, 'status' => 'published', 'attendance_rule' => 'end_only', 'opens_at' => now()->subDays(2), 'closes_at' => now()->subHours(3), 'starts_at' => now()->subHours(2), 'ends_at' => now()->subMinute()]);
        $r = $this->reg($e, $u);

        return [$e, $a, $u, $r, $crit, $sem];
    }

    private function reg(Event $e, User $u): Registration
    {
        return Registration::create(['event_id' => $e->id, 'user_id' => $u->id, 'reference' => (string) Str::uuid(), 'status' => 'confirmed', 'submitted_at' => now()->subDay(), 'snapshot' => ['name' => $u->name, 'student_code' => $u->student_code, 'class_name' => $u->class_name, 'email' => $u->email, 'source' => 'test_fixture', 'event_title' => $e->title, 'closes_at' => $e->closes_at->toIso8601String(), 'proposed_points' => 3]]);
    }

    private function finish(Event $e, User $a, User $u): void
    {
        $s = app(AttendanceService::class);
        $session = $s->open($e, $a);
        $s->complete($e, $u, $s->payload($session));
    }

    private function invalid(callable $f): void
    {
        try {
            $f();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_opens_only_after_end_and_ttl_is_exactly_600_seconds(): void
    {
        [$e,$a] = $this->setupEvent();
        $service = app(AttendanceService::class);
        $e->update(['ends_at' => now()->addMinute()]);
        $this->invalid(fn () => $service->open($e, $a));
        $e->update(['ends_at' => now()]);
        $s = $service->open($e, $a);
        $this->assertEquals(600, $s->opened_at->diffInSeconds($s->expires_at));
    }

    public function test_double_open_does_not_extend_and_reopen_requires_reason(): void
    {
        [$e,$a] = $this->setupEvent();
        $v = app(AttendanceService::class);
        $s = $v->open($e, $a);
        $this->travel(30)->seconds();
        $again = $v->open($e, $a);
        $this->assertSame($s->id, $again->id);
        $this->assertTrue($s->expires_at->equalTo($again->expires_at));
        $v->close($e, $a);
        $this->invalid(fn () => $v->open($e, $a));
        $new = $v->open($e, $a, 'Khắc phục kết nối');
        $this->assertNotSame($s->id, $new->id);
    }

    public function test_ttl_accepts_599_seconds_and_rejects_exact_expiry(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        $v = app(AttendanceService::class);
        $s = $v->open($e, $a);
        $this->travelTo($s->opened_at->copy()->addSeconds(599));
        $v->complete($e, $u, $v->payload($s));
        $this->assertDatabaseCount('event_completions', 1);
        $other = $this->user();
        $this->reg($e, $other);
        $this->travelTo($s->expires_at);
        $this->invalid(fn () => $v->complete($e, $other, $v->payload($s)));
    }

    public function test_revoked_session_and_cancelled_event_rejected(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        $v = app(AttendanceService::class);
        $s = $v->open($e, $a);
        $p = $v->payload($s);
        $v->close($e, $a);
        $this->invalid(fn () => $v->complete($e, $u, $p));
        $s = $v->open($e, $a, 'Mở lại');
        $e->update(['status' => 'cancelled']);
        $this->invalid(fn () => $v->complete($e, $u, $v->payload($s)));
    }

    public function test_rotating_signature_rejects_old_screenshots_and_forgery(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        $v = app(AttendanceService::class);
        $s = $v->open($e, $a);
        $p = $v->payload($s);
        $this->invalid(fn () => $v->complete($e, $u, substr($p, 0, -1).(substr($p, -1) === 'a' ? 'b' : 'a')));
        $this->travel(31)->seconds();
        $this->invalid(fn () => $v->complete($e, $u, $p));
        $v->complete($e, $u, $v->payload($s));
        $this->assertDatabaseCount('event_completions', 1);
    }

    public function test_duplicate_is_idempotent_and_no_automatic_points(): void
    {
        [$e,$a,$u,$r] = $this->setupEvent();
        $v = app(AttendanceService::class);
        $s = $v->open($e, $a);
        $v->complete($e, $u, $v->payload($s));
        $again = $v->complete($e, $u, $v->payload($s));
        $this->assertTrue($again['duplicate']);
        $this->assertDatabaseCount('event_completions', 1);
        $this->assertDatabaseCount('student_points', 0);
        $this->assertSame('attended_complete', $r->fresh()->attendance_status);
    }

    public function test_waitlist_cancelled_unknown_and_locked_students_rejected(): void
    {
        [$e,$a,$u,$r] = $this->setupEvent();
        $v = app(AttendanceService::class);
        $s = $v->open($e, $a);
        foreach (['waitlist', 'cancelled'] as $status) {
            $r->update(['status' => $status]);
            $this->invalid(fn () => $v->complete($e, $u, $v->payload($s)));
        }$r->update(['status' => 'confirmed']);
        $u->update(['is_active' => false]);
        $this->invalid(fn () => $v->complete($e, $u, $v->payload($s)));
        $this->invalid(fn () => $v->complete($e, $this->user(), $v->payload($s)));
    }

    public function test_student_can_only_record_own_identity(): void
    {
        [$e,$a,$u,$r] = $this->setupEvent();
        $other = $this->user();
        $target = $this->reg($e, $other);
        $v = app(AttendanceService::class);
        $s = $v->open($e, $a);
        $this->actingAs($u)->post('/events/'.$e->id.'/attendance/complete', ['code' => $v->payload($s), 'user_id' => $other->id, 'registration_id' => $target->id])->assertRedirect();
        $this->assertDatabaseHas('event_completions', ['user_id' => $u->id]);
        $this->assertDatabaseMissing('event_completions', ['user_id' => $other->id]);
    }

    public function test_cross_event_token_rejected(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        $v = app(AttendanceService::class);
        $s = $v->open($e, $a);
        $second = $e->replicate();
        $second->title = 'Khác';
        $second->save();
        $this->reg($second, $u);
        $this->invalid(fn () => $v->complete($second, $u, $v->payload($s)));
    }

    public function test_staff_permission_and_retired_endpoints(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        $this->actingAs($u)->post('/events/'.$e->id.'/attendance/open')->assertForbidden();
        $this->get('/events/'.$e->id.'/attendance/qr')->assertForbidden();
        $this->post('/events/'.$e->id.'/attendance/check-in', ['identifier' => $u->student_code])->assertNotFound();
        EventStaff::create(['event_id' => $e->id, 'user_id' => $u->id, 'role' => 'scanner', 'assigned_by' => $a->id]);
        $this->get('/events/'.$e->id.'/attendance/scan')->assertOk();
        $this->post('/events/'.$e->id.'/attendance/open')->assertRedirect();
        $this->get('/events/'.$e->id.'/points/review')->assertForbidden();
    }

    public function test_qr_endpoint_no_store_and_models_hide_secret(): void
    {
        [$e,$a] = $this->setupEvent();
        app(AttendanceService::class)->open($e, $a);
        $res = $this->actingAs($a)->getJson('/events/'.$e->id.'/attendance/qr')->assertOk()->assertJsonPath('active', true);
        $this->assertStringContainsString('<svg', $res->json('svg'));
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $data = AttendanceSession::first()->toArray();
        $this->assertArrayNotHasKey('secret_ciphertext', $data);
        $this->assertArrayNotHasKey('token_hash', $data);
    }

    public function test_paper_unknown_student_cannot_update_first_registration(): void
    {
        [$e,$a,$u,$r] = $this->setupEvent();
        $v = app(AttendanceService::class);
        $this->invalid(fn () => $v->importPaperAttendance($e, [['student_code' => '9999999999', 'has_check_out' => true]], 'scan.pdf', 'Có xác nhận', $a));
        $this->assertDatabaseCount('event_completions', 0);
        $this->assertSame('registered', $r->fresh()->attendance_status);
        $this->assertDatabaseCount('paper_attendances', 0);
    }

    public function test_paper_explicit_presence_scan_and_notes_required_deduplicated(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        $v = app(AttendanceService::class);
        $this->invalid(fn () => $v->importPaperAttendance($e, [['student_code' => $u->student_code]], 'scan.pdf', 'Có xác nhận', $a));
        $rows = [['student_code' => $u->student_code, 'has_check_out' => true]];
        $v->importPaperAttendance($e, $rows, 'scan.pdf', 'Có chữ ký', $a);
        $v->importPaperAttendance($e, $rows, 'scan2.pdf', 'Đối soát lại', $a);
        $this->assertDatabaseCount('event_completions', 1);
        $this->assertDatabaseCount('student_points', 0);
    }

    public function test_points_require_completion_and_are_idempotent_after_revocation(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        $p = app(PointService::class);
        $this->assertSame(0, $p->approveEventPoints($e, $a)['approved']);
        $this->finish($e, $a, $u);
        $p->approveEventPoints($e, $a);
        $sp = StudentPoint::firstOrFail();
        $this->assertSame(3, $sp->points);
        $p->adjustPoints($sp, 0, 'Thu hồi để xác minh', $a);
        $p->approveEventPoints($e, $a);
        $this->assertSame('revoked', $sp->fresh()->status);
        $this->assertDatabaseCount('student_points', 1);
    }

    public function test_cap_across_events_and_adjustment_enforces_total(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        $this->finish($e, $a, $u);
        $p = app(PointService::class);
        $p->approveEventPoints($e, $a);
        $second = $e->replicate();
        $second->title = 'Sự kiện thứ hai';
        $second->save();
        $this->reg($second, $u);
        $this->finish($second, $a, $u);
        $res = $p->approveEventPoints($second, $a);
        $this->assertSame(1, $res['capped']);
        $this->assertSame(5, (int) StudentPoint::sum('points'));
        $sp = StudentPoint::where('event_id', $second->id)->firstOrFail();
        $this->invalid(fn () => $p->adjustPoints($sp, 3, 'Tăng lại', $a));
        $this->assertSame(5, (int) StudentPoint::sum('points'));
    }

    public function test_point_permission_enforced_in_service(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        $this->finish($e, $a, $u);
        $this->actingAs($u)->post('/events/'.$e->id.'/points/approve')->assertForbidden();
        app(PointService::class)->approveEventPoints($e, $a);
        $this->actingAs($u)->post('/points/'.StudentPoint::first()->id.'/adjust', ['new_points' => 5, 'reason' => 'Thử'])->assertForbidden();
    }

    public function test_resolved_dispute_does_not_fabricate_attendance(): void
    {
        [$e,$a,$u,$r] = $this->setupEvent();
        $d = app(DisputeService::class)->createTicket($u, $e, ['type' => 'missing_attendance', 'description' => 'Thiếu dữ liệu']);
        app(DisputeService::class)->processTicket($d, 'resolved', 'Đã phản hồi; cần đối soát giấy riêng', $a);
        $this->assertDatabaseCount('event_completions', 0);
        $this->assertNull($r->fresh()->check_in_at);
        $this->assertSame('registered', $r->fresh()->attendance_status);
    }

    public function test_private_paper_and_dispute_files(): void
    {
        Storage::fake('local');
        [$e,$a,$u] = $this->setupEvent();
        Storage::disk('local')->put('scan.pdf', 'private');
        $p = PaperAttendance::create(['event_id' => $e->id, 'entered_by' => $a->id, 'scan_file_path' => 'scan.pdf', 'total_records' => 1]);
        $this->actingAs($u)->get('/attendance/paper/'.$p->id.'/file')->assertForbidden();
        $this->actingAs($a)->get('/attendance/paper/'.$p->id.'/file')->assertOk();
        $d = app(DisputeService::class)->createTicket($u, $e, ['type' => 'missing_points', 'description' => 'Thiếu điểm'], 'scan.pdf');
        $this->actingAs($this->user())->get('/disputes/'.$d->id.'/evidence')->assertForbidden();
        $this->actingAs($u)->get('/disputes/'.$d->id.'/evidence')->assertOk();
    }

    public function test_event_rule_frozen_and_venues_validated(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        app(AttendanceService::class)->open($e, $a);
        $d = $e->only(['title', 'description', 'category_id', 'semester_id', 'criteria_id', 'venue', 'capacity', 'proposed_points', 'status']);
        $d['attendance_rule'] = 'end_only';
        foreach (['opens_at', 'closes_at', 'starts_at', 'ends_at'] as $f) {
            $d[$f] = $e->$f->copy()->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i');
        }$d['proposed_points'] = 5;
        $this->actingAs($a)->put('/admin/events/'.$e->id, $d)->assertSessionHasErrors('event');
        $d['venue'] = 'Hội trường A';
        $this->put('/admin/events/'.$e->id, $d)->assertSessionHasErrors('venue');
    }

    public function test_waitlist_promotion_has_no_permanent_qr(): void
    {
        [$e,$a,$u] = $this->setupEvent();
        $e->update(['opens_at' => now()->subDay(), 'closes_at' => now()->addHour(), 'capacity' => 1]);
        $other = $this->user();
        $service = app(RegistrationService::class);
        $waiting = $service->register($e, $other, true);
        $this->assertSame('waitlist', $waiting->status);
        $service->cancel(Registration::where('user_id', $u->id)->first(), $u);
        $this->assertSame('confirmed', $waiting->fresh()->status);
        $this->assertNull($waiting->fresh()->qr_token);
    }

    public function test_import_preview_bound_to_event_and_source_time_required(): void
    {
        [$e,$a] = $this->setupEvent();
        $this->actingAs($a)->post('/admin/events/'.$e->id.'/import/commit')->assertStatus(422);
    }

    public function test_demo_seeder_creates_all_five_venues_and_end_demo(): void
    {
        $this->seed();
        foreach (EventVenue::values() as $venue) {
            $this->assertDatabaseHas('events', ['venue' => $venue]);
        }$this->assertDatabaseCount('event_completions', 0);
        $this->assertDatabaseCount('student_points', 0);
    }

    public function test_next_approval_batch_skips_already_recorded_students(): void
    {
        [$e, $a, $u] = $this->setupEvent();
        $this->finish($e, $a, $u);
        $service = app(PointService::class);
        $service->approveEventPoints($e, $a);
        $second = $this->user();
        $this->reg($e, $second);
        $this->finish($e, $a, $second);
        $result = $service->approveEventPoints($e, $a);
        $this->assertSame(1, $result['eligible']);
        $this->assertSame(1, $result['approved']);
        $this->assertSame(0, $result['already_recorded']);
        $this->assertSame(2, StudentPoint::where('event_id', $e->id)->count());
    }

    public function test_close_and_reopen_in_same_second_selects_the_new_active_session(): void
    {
        [$e, $a] = $this->setupEvent();
        $service = app(AttendanceService::class);
        $old = $service->open($e, $a);
        $service->close($e, $a);
        $new = $service->open($e, $a, 'Khắc phục gián đoạn');
        $this->assertNotSame($old->id, $new->id);
        $this->assertSame($new->id, $service->current($e)->id);
        $this->assertSame($new->id, $service->open($e, $a)->id);
    }

    public function test_legacy_status_alone_does_not_count_as_new_completion_in_reports(): void
    {
        [$e, $a, $u, $r] = $this->setupEvent();
        $r->update(['attendance_status' => 'attended_complete', 'check_out_at' => now()]);
        $report = app(ReportService::class)->getEventReport($e);
        $this->assertSame(0, $report['attended_complete']);
        $this->assertSame(1, $report['attended_incomplete']);
        $this->actingAs($a)->get('/admin/events/'.$e->id.'/participants')->assertOk()->assertSee('Cần xác minh');
        $this->finish($e, $a, $u);
        $this->assertSame(1, app(ReportService::class)->getEventReport($e)['attended_complete']);
    }
}
