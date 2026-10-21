<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monitor_exam_readinesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('monitor_id')->constrained('monitors')->cascadeOnDelete();
            $table->boolean('is_ready')->default(false);
            $table->unsignedSmallInteger('additional_hours')->nullable();
            $table->string('practice_frequency', 120)->nullable();
            $table->text('observation')->nullable();
            $table->timestamp('ready_at')->nullable();
            // Advances on each notification-worthy ready state, so a later
            // condition update can be notified while unchanged submissions
            // remain deduplicated.
            $table->unsignedInteger('notification_sequence')->default(0);
            $table->timestamps();

            $table->unique('student_id');
        });

        Schema::create('monitor_exam_readiness_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_readiness_id')
                ->constrained('monitor_exam_readinesses')
                ->cascadeOnDelete();
            $table->foreignId('secretary_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('state_hash', 64);
            $table->json('notification_data');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['exam_readiness_id', 'secretary_user_id', 'state_hash'], 'monitor_exam_readiness_notification_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_exam_readiness_notifications');
        Schema::dropIfExists('monitor_exam_readinesses');
    }
};
