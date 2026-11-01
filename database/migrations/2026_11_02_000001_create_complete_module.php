<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Học kỳ
        Schema::create('semesters', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name', 100);
            $t->date('starts_at');
            $t->date('ends_at');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // 2. Tiêu chí rèn luyện
        Schema::create('criterias', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name', 180);
            $t->unsignedInteger('max_points')->default(20);
            $t->string('rule_version', 50)->default('QĐ-HUIT-2026');
            $t->text('description')->nullable();
            $t->timestamps();
        });

        // 3. Bổ sung trường cho events
        Schema::table('events', function (Blueprint $t) {
            $t->foreignId('semester_id')->nullable()->after('category_id')->constrained('semesters')->nullOnDelete();
            $t->foreignId('criteria_id')->nullable()->after('semester_id')->constrained('criterias')->nullOnDelete();
            $t->string('attendance_rule', 20)->default('in_and_out')->after('proposed_points'); // 'in_only', 'in_and_out'
            $t->unsignedInteger('min_minutes_between')->default(0)->after('attendance_rule');
            $t->dateTime('dispute_closes_at')->nullable()->after('ends_at');
        });

        // 4. Bổ sung trường cho registrations
        Schema::table('registrations', function (Blueprint $t) {
            $t->string('qr_token', 64)->nullable()->unique()->after('reference');
            $t->string('attendance_status', 30)->default('registered')->after('status'); 
            // registered, attended_complete, attended_incomplete, needs_verification, absent
            $t->dateTime('check_in_at')->nullable()->after('attendance_status');
            $t->dateTime('check_out_at')->nullable()->after('check_in_at');
            $t->string('attendance_source', 20)->default('none')->after('check_out_at'); // none, qr, paper, both
            $t->boolean('is_waitlist')->default(false)->after('attendance_source');
            $t->unsignedInteger('waitlist_position')->nullable()->after('is_waitlist');
            
            $t->index(['event_id', 'attendance_status']);
            $t->index(['event_id', 'is_waitlist', 'waitlist_position']);
        });

        // 5. Đợt nhập giấy dự phòng
        Schema::create('paper_attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $t->string('scan_file_path')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedInteger('total_records')->default(0);
            $t->timestamps();
        });

        // 6. Chi tiết lượt điểm danh (QR / giấy)
        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('student_code', 30);
            $t->string('student_name', 100)->nullable();
            $t->string('class_name', 50)->nullable();
            $t->string('type', 20); // 'check_in', 'check_out'
            $t->string('method', 20); // 'qr_scan', 'paper_manual'
            $t->foreignId('scanned_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('scanned_at');
            $t->string('device_info', 100)->nullable();
            $t->foreignId('paper_sheet_id')->nullable()->constrained('paper_attendances')->cascadeOnDelete();
            $t->timestamps();

            // Chống quét lặp hoặc nhập trùng 1 loại check-in / check-out
            $t->unique(['event_id', 'student_code', 'type']);
            $t->index(['event_id', 'student_code']);
        });

        // 7. Ghi nhận điểm rèn luyện
        Schema::create('student_points', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $t->foreignId('criteria_id')->constrained('criterias')->restrictOnDelete();
            $t->foreignId('semester_id')->constrained('semesters')->restrictOnDelete();
            $t->unsignedInteger('points')->default(0);
            $t->string('status', 20)->default('approved'); // 'proposed', 'approved', 'revoked'
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('approved_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();

            // CHỐNG CỘNG TRÙNG IDEMPOTENCY: Một sinh viên chỉ có 1 bản ghi điểm cho cùng 1 sự kiện, tiêu chí và học kỳ
            $t->unique(['student_id', 'event_id', 'criteria_id', 'semester_id'], 'unique_student_event_criteria_sem');
            $t->index(['student_id', 'semester_id']);
        });

        // 8. Lịch sử điều chỉnh / thu hồi điểm
        Schema::create('point_adjustments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_point_id')->constrained('student_points')->cascadeOnDelete();
            $t->foreignId('adjusted_by')->constrained('users')->restrictOnDelete();
            $t->integer('old_points');
            $t->integer('new_points');
            $t->text('reason'); // Bắt buộc nhập lý do
            $t->timestamps();
        });

        // 9. Khiếu nại / Phản hồi sai sót
        Schema::create('disputes', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code', 30)->unique();
            $t->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('registration_id')->nullable()->constrained('registrations')->nullOnDelete();
            $t->string('type', 30); // missing_attendance, missing_checkout, missing_points, incorrect_points
            $t->text('description');
            $t->string('evidence_file_path')->nullable();
            $t->string('status', 20)->default('pending'); // pending, in_review, resolved, rejected
            $t->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('handled_at')->nullable();
            $t->text('resolution_notes')->nullable();
            $t->timestamps();

            $t->index(['event_id', 'status']);
            $t->index(['user_id', 'status']);
        });

        // 10. Phân công nhân sự theo sự kiện
        Schema::create('event_staffs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('role', 30); // 'scanner', 'approver', 'manager'
            $t->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();

            $t->unique(['event_id', 'user_id', 'role']);
        });

        // 11. File import và audit import
        Schema::create('imported_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->string('original_name', 255);
            $t->string('stored_path', 255);
            $t->string('type', 30); // registrations, attendance
            $t->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $t->unsignedInteger('total_rows')->default(0);
            $t->unsignedInteger('success_rows')->default(0);
            $t->unsignedInteger('duplicate_rows')->default(0);
            $t->dateTime('source_registered_at')->nullable();
            $t->dateTime('imported_at');
            $t->json('summary_json')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_files');
        Schema::dropIfExists('event_staffs');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('point_adjustments');
        Schema::dropIfExists('student_points');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('paper_attendances');

        Schema::table('registrations', function (Blueprint $t) {
            $t->dropColumn([
                'qr_token',
                'attendance_status',
                'check_in_at',
                'check_out_at',
                'attendance_source',
                'is_waitlist',
                'waitlist_position',
            ]);
        });

        Schema::table('events', function (Blueprint $t) {
            $t->dropForeign(['semester_id']);
            $t->dropForeign(['criteria_id']);
            $t->dropColumn([
                'semester_id',
                'criteria_id',
                'attendance_rule',
                'min_minutes_between',
                'dispute_closes_at',
            ]);
        });

        Schema::dropIfExists('criterias');
        Schema::dropIfExists('semesters');
    }
};
