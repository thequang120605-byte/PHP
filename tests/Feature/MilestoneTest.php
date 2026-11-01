<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MilestoneTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email = 'student@test.com'): User
    {
        return User::create(['name' => 'Sinh viên thử', 'email' => $email, 'password' => 'Student@12345', 'student_code' => (string) random_int(1000000000, 1999999999), 'class_name' => '14DHTH']);
    }

    private function event(array $extra = []): Event
    {
        $a = $this->user('admin@test.com');
        $a->forceFill(['role' => 'admin'])->save();
        $c = Category::create(['name' => 'Học thuật']);

        return Event::create(array_merge(['title' => 'Chuyên đề AI', 'description' => 'Nội dung demo', 'category_id' => $c->id, 'created_by' => $a->id, 'venue' => 'Hội trường C', 'capacity' => 1, 'proposed_points' => 3, 'status' => 'published', 'opens_at' => now()->subDay(), 'closes_at' => now()->addDay(), 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHours(2)], $extra));
    }

    private function form(Event $e): array
    {
        $d = $e->only(['title', 'description', 'category_id', 'venue', 'capacity', 'proposed_points', 'status']);
        foreach (['opens_at', 'closes_at', 'starts_at', 'ends_at'] as $f) {
            $d[$f] = $e->$f->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i');
        }

        return $d;
    }

    public function test_public_pages_and_filters(): void
    {
        $e = $this->event();
        $this->get('/')->assertOk()->assertSee('Chuyên đề AI');
        $this->get('/?q=zzzz')->assertOk()->assertDontSee('Chuyên đề AI');
        $this->get('/events/'.$e->id)->assertOk()->assertSee('3 điểm đề xuất');
    }

    public function test_guest_and_student_permissions(): void
    {
        $e = $this->event();
        $this->post('/events/'.$e->id.'/register')->assertRedirect('/login');
        $this->actingAs($this->user())->get('/admin')->assertForbidden();
        $this->post('/admin/events', [])->assertForbidden();
    }

    public function test_signup_cannot_set_admin(): void
    {
        $this->post('/register', ['name' => 'Nguyễn A', 'student_code' => '2001234567', 'class_name' => '14DHTH', 'email' => 'new@test.com', 'password' => 'Student@12345', 'password_confirmation' => 'Student@12345', 'role' => 'admin'])->assertRedirect('/');
        $u = User::where('email', 'new@test.com')->firstOrFail();
        $this->assertSame('student', $u->role);
        $this->assertTrue(Hash::check('Student@12345', $u->password));
    }

    public function test_login_logout_and_locked_account(): void
    {
        $u = $this->user();
        $this->post('/login', ['email' => $u->email, 'password' => 'Student@12345'])->assertRedirect('/');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $u->forceFill(['is_active' => false])->save();
        $this->post('/login', ['email' => $u->email, 'password' => 'Student@12345'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_lock_during_session_revokes_access(): void
    {
        $u = $this->user();
        $this->actingAs($u);
        $u->forceFill(['is_active' => false])->save();
        $this->get('/my-registrations')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_rate_limiter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'limit@test.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }$this->post('/login', ['email' => 'limit@test.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertStringContainsString('Vui lòng chờ', session('errors')->first('email'));
    }

    public function test_receipt_snapshot_and_audit(): void
    {
        $e = $this->event();
        $u = $this->user();
        $this->actingAs($u)->post('/events/'.$e->id.'/register')->assertRedirect();
        $r = Registration::firstOrFail();
        $this->assertSame($u->student_code, $r->snapshot['student_code']);
        $this->assertNotNull($r->submitted_at);
        $this->get('/registrations/'.$r->id)->assertOk()->assertSee('chưa cộng điểm');
        $this->assertDatabaseHas('audit_logs', ['action' => 'registration.confirmed']);
    }

    public function test_duplicate_and_capacity_rejected(): void
    {
        $e = $this->event();
        $a = $this->user();
        $b = $this->user('second@test.com');
        $this->actingAs($a)->post('/events/'.$e->id.'/register')->assertRedirect();
        $this->post('/events/'.$e->id.'/register')->assertSessionHasErrors('registration');
        $this->actingAs($b)->post('/events/'.$e->id.'/register')->assertSessionHasErrors('registration');
        $this->assertDatabaseCount('registrations', 1);
        $this->assertSame(2, AuditLog::where('action', 'registration.rejected')->count());
    }

    public function test_future_closed_boundary_cancelled(): void
    {
        $e = $this->event(['opens_at' => now()->addHour()]);
        $this->actingAs($this->user());
        $this->post('/events/'.$e->id.'/register')->assertSessionHasErrors('registration');
        $e->update(['opens_at' => now()->subDay(), 'closes_at' => now()]);
        $this->post('/events/'.$e->id.'/register')->assertSessionHasErrors('registration');
        $e->update(['closes_at' => now()->addDay(), 'status' => 'cancelled']);
        $this->post('/events/'.$e->id.'/register')->assertSessionHasErrors('registration');
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_cancel_idempotent_reregister_new_reference(): void
    {
        $e = $this->event();
        $u = $this->user();
        $s = app(RegistrationService::class);
        $r = $s->register($e, $u);
        $old = $r->reference;
        $s->cancel($r, $u);
        $s->cancel($r, $u);
        $r = $s->register($e, $u);
        $this->assertNotSame($old, $r->reference);
        $this->assertDatabaseCount('registrations', 1);
        $this->assertSame(3, AuditLog::where('entity_type', 'registration')->count());
    }

    public function test_cross_user_receipt_and_cancel_forbidden(): void
    {
        $e = $this->event();
        $a = $this->user();
        $b = $this->user('second@test.com');
        $r = app(RegistrationService::class)->register($e, $a);
        $this->actingAs($b)->get('/registrations/'.$r->id)->assertForbidden();
        $this->post('/registrations/'.$r->id.'/cancel')->assertForbidden();
        $this->assertSame('confirmed', $r->fresh()->status);
    }

    public function test_profile_keeps_original_evidence(): void
    {
        $e = $this->event();
        $u = $this->user();
        $r = app(RegistrationService::class)->register($e, $u);
        $old = $u->name;
        $this->actingAs($u)->put('/profile', ['name' => 'Tên mới', 'student_code' => $u->student_code, 'class_name' => 'Lớp mới'])->assertRedirect();
        $this->assertSame($old, $r->fresh()->snapshot['name']);
    }

    public function test_draft_hidden_cancelled_visible_to_participant(): void
    {
        $e = $this->event(['status' => 'draft']);
        $this->get('/')->assertDontSee('Chuyên đề AI');
        $this->get('/events/'.$e->id)->assertNotFound();
        $e->update(['status' => 'published']);
        $u = $this->user();
        app(RegistrationService::class)->register($e, $u);
        $e->update(['status' => 'cancelled']);
        $this->actingAs($u)->get('/events/'.$e->id)->assertOk();
    }

    public function test_admin_pages_and_csv(): void
    {
        $e = $this->event();
        $u = $this->user();
        app(RegistrationService::class)->register($e, $u);
        $this->actingAs(User::find($e->created_by));
        foreach (['/admin', '/admin/events/create', '/admin/events/'.$e->id.'/edit', '/admin/events/'.$e->id.'/participants', '/admin/categories'] as $url) {
            $this->get($url)->assertOk();
        }$res = $this->get('/admin/events/'.$e->id.'/export')->assertOk();
        $this->assertStringContainsString($u->student_code, $res->streamedContent());
    }

    public function test_no_capacity_shrink_or_delete_when_registered(): void
    {
        $e = $this->event(['capacity' => 2]);
        app(RegistrationService::class)->register($e, $this->user());
        app(RegistrationService::class)->register($e, $this->user('second@test.com'));
        $this->actingAs(User::find($e->created_by));
        $d = $this->form($e);
        $d['capacity'] = 1;
        $this->put('/admin/events/'.$e->id, $d)->assertSessionHasErrors('capacity');
        $this->delete('/admin/events/'.$e->id)->assertSessionHasErrors('event');
        $this->assertSame(2, $e->fresh()->capacity);
    }

    public function test_event_crud_and_timezone(): void
    {
        $e = $this->event();
        $this->actingAs(User::find($e->created_by));
        $d = $this->form($e);
        $d['title'] = 'Sự kiện mới';
        $this->post('/admin/events', $d)->assertRedirect();
        $n = Event::where('title', 'Sự kiện mới')->firstOrFail();
        $this->assertSame($d['starts_at'], $n->starts_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i'));
        $d['title'] = 'Đã sửa';
        $this->put('/admin/events/'.$n->id, $d)->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'event.updated', 'entity_id' => $n->id]);
        $this->delete('/admin/events/'.$n->id)->assertRedirect();
        $this->assertDatabaseMissing('events', ['id' => $n->id]);
    }

    public function test_invalid_upload_and_category_crud(): void
    {
        $e = $this->event();
        $this->actingAs(User::find($e->created_by));
        $d = $this->form($e);
        $d['poster'] = UploadedFile::fake()->create('script.php', 10, 'application/x-php');
        $this->put('/admin/events/'.$e->id, $d)->assertSessionHasErrors('poster');
        $this->post('/admin/categories', ['name' => 'Mới'])->assertRedirect();
        $c = Category::where('name', 'Mới')->firstOrFail();
        $this->put('/admin/categories/'.$c->id, ['name' => 'Đổi tên'])->assertRedirect();
        $this->delete('/admin/categories/'.$c->id)->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $c->id]);
        $this->delete('/admin/categories/'.$e->category_id)->assertSessionHasErrors('category');
    }
}
