<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('student_code', 20)->nullable()->unique();
            $t->string('class_name', 50)->nullable();
            $t->string('role', 20)->default('student');
            $t->boolean('is_active')->default(true);
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100)->unique();
            $t->timestamps();
        });
        Schema::create('events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->string('title', 180);
            $t->text('description');
            $t->string('venue', 180);
            $t->string('poster_path')->nullable();
            $t->dateTime('opens_at')->index();
            $t->dateTime('closes_at')->index();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->unsignedInteger('capacity');
            $t->unsignedTinyInteger('proposed_points')->default(0);
            $t->string('status', 20)->default('draft')->index();
            $t->timestamps();
        });
        Schema::create('registrations', function (Blueprint $t) {
            $t->id();
            $t->uuid('reference')->unique();
            $t->foreignId('event_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('status', 20)->default('confirmed');
            $t->dateTime('submitted_at')->index();
            $t->dateTime('cancelled_at')->nullable();
            $t->json('snapshot');
            $t->timestamps();
            $t->unique(['event_id', 'user_id']);
            $t->index(['event_id', 'status']);
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 60);
            $t->string('entity_type', 30);
            $t->unsignedBigInteger('entity_id');
            $t->json('payload');
            $t->timestamps();
            $t->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('events');
        Schema::dropIfExists('categories');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['student_code', 'class_name', 'role', 'is_active']));
    }
};
