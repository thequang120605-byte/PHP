<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fail before DDL if historical point data needs human reconciliation.
        if (DB::table('student_points')->select('student_id', 'event_id')->groupBy('student_id', 'event_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Có điểm trùng sinh viên/sự kiện. Đối soát bản ghi trước khi migrate; không tự xóa lịch sử.');
        }
        Schema::create('attendance_sessions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('event_id')->constrained()->restrictOnDelete();
            $t->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $t->text('secret_ciphertext');
            $t->string('token_hash', 64);
            $t->dateTime('opened_at');
            $t->dateTime('expires_at')->index();
            $t->dateTime('revoked_at')->nullable();
            $t->json('point_rule_snapshot');
            $t->string('reason', 500)->nullable();
            $t->timestamps();
            $t->index(['event_id', 'expires_at']);
        });
        Schema::create('event_completions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('registration_id')->unique()->constrained()->restrictOnDelete();
            $t->uuid('attendance_session_id')->nullable();
            $t->foreign('attendance_session_id')->references('id')->on('attendance_sessions')->restrictOnDelete();
            $t->string('method', 20);
            $t->dateTime('completed_at');
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('paper_sheet_id')->nullable()->constrained('paper_attendances')->restrictOnDelete();
            $t->json('rule_snapshot');
            $t->timestamps();
            $t->unique(['event_id', 'user_id']);
        });
        Schema::table('student_points', function (Blueprint $t) {
            $t->json('rule_snapshot')->nullable();
            $t->unique(['student_id', 'event_id'], 'one_point_per_student_event');
            $t->index(['student_id', 'semester_id', 'criteria_id', 'status'], 'point_cap_lookup');
        });
        Schema::table('registrations', fn (Blueprint $t) => $t->dateTime('completed_at')->nullable());
        DB::table('events')->update(['attendance_rule' => 'end_only', 'min_minutes_between' => 0]);
        // Only migrate known demo labels; preserve custom historical locations.
        foreach (['Hội trường A' => 'Hội trường C', 'Hội trường B' => 'Phòng hội thảo', 'Phòng B203' => 'Tầng 3 thư viện', 'Sân trường HUIT' => 'Sân trường'] as $old => $new) {
            DB::table('events')->where('venue', $old)->update(['venue' => $new]);
        }
    }

    public function down(): void
    {
        Schema::table('student_points', function (Blueprint $t) {
            $t->dropUnique('one_point_per_student_event');
            $t->dropIndex('point_cap_lookup');
            $t->dropColumn('rule_snapshot');
        });
        Schema::table('registrations', fn (Blueprint $t) => $t->dropColumn('completed_at'));
        Schema::dropIfExists('event_completions');
        Schema::dropIfExists('attendance_sessions');
    }
};
