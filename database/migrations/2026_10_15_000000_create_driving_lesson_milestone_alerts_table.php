<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driving_lesson_milestone_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            // 10 for an automatic student, 15 for a manual student.
            $table->unsignedSmallInteger('milestone_hour');
            $table->unsignedInteger('completed_minutes');
            $table->uuid('reservation_id')->nullable()->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->uuid('acknowledged_by_user_id')->nullable();
            $table->timestamps();

            // This is the durable de-duplication rule: one alert for one
            // student/milestone, even if the page is refreshed or the lesson
            // is later rescheduled.
            $table->unique(['student_id', 'milestone_hour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driving_lesson_milestone_alerts');
    }
};
