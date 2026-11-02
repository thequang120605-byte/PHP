<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cột chốt kết quả sự kiện (Finalization)
        Schema::table('events', function (Blueprint $t) {
            $t->boolean('is_finalized')->default(false)->after('status');
            $t->dateTime('finalized_at')->nullable()->after('is_finalized');
            $t->foreignId('finalized_by')->nullable()->after('finalized_at')->constrained('users')->nullOnDelete();
            $t->text('finalized_notes')->nullable()->after('finalized_by');
        });

        // 2. Bảng thông báo nghiệp vụ trong ứng dụng (In-App Notifications)
        Schema::create('app_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('title', 180);
            $t->text('message');
            $t->string('type', 30)->default('info'); // registration, point, dispute, attendance, event_update, finalization
            $t->string('icon', 30)->default('bell'); // ticket, star, check, alert, clock, bell
            $t->string('link', 255)->nullable();
            $t->boolean('is_read')->default(false)->index();
            $t->timestamps();

            $t->index(['user_id', 'is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::table('events', function (Blueprint $t) {
            $t->dropForeign(['finalized_by']);
            $t->dropColumn(['is_finalized', 'finalized_at', 'finalized_by', 'finalized_notes']);
        });
    }
};
