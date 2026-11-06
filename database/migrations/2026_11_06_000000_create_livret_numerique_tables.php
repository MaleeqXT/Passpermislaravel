<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('livret_training_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('sync_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider_contract_id', 100)->nullable()->unique();
            $table->string('request_number', 100)->index();
            $table->date('registration_date');
            $table->string('training_type', 100);
            $table->string('permit_category', 100);
            $table->string('vehicle_type', 100);
            $table->unsignedSmallInteger('evaluated_hours');
            $table->string('sync_status', 32)->default('pending')->index();
            $table->unsignedSmallInteger('sync_attempts')->default(0);
            $table->string('provider_error_code', 100)->nullable();
            $table->text('provider_error_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('last_sync_attempt_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'request_number', 'permit_category'], 'livret_contract_student_request_permit_unique');
        });

        Schema::create('livret_lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('training_contract_id')->constrained('livret_training_contracts')->cascadeOnDelete();
            $table->foreignId('sync_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider_contract_id', 100)->nullable();
            $table->string('provider_lesson_id', 100)->nullable()->unique();
            $table->string('local_session_reference', 100)->nullable()->index();
            $table->date('lesson_date');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('lesson_type', 32);
            $table->string('permit_category', 100);
            $table->string('vehicle_type', 100);
            $table->string('trainer_id', 100)->nullable();
            $table->string('provider_trainer_id', 100)->nullable();
            $table->string('trainer_authorization', 100)->nullable();
            $table->string('simulator_id', 100)->nullable();
            $table->string('provider_simulator_id', 100)->nullable();
            $table->string('simulator_reference', 100)->nullable();
            $table->string('department_code', 10)->nullable();
            $table->string('sync_status', 32)->default('pending')->index();
            $table->unsignedSmallInteger('sync_attempts')->default(0);
            $table->string('provider_error_code', 100)->nullable();
            $table->text('provider_error_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('last_sync_attempt_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['training_contract_id', 'lesson_date', 'started_at', 'lesson_type', 'local_session_reference'], 'livret_lesson_duplicate_guard');
        });

        Schema::create('livret_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('resource_type', 50);
            $table->uuid('resource_id');
            $table->string('provider_resource_id', 100)->nullable();
            $table->string('operation', 100);
            $table->string('endpoint', 255)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('provider_error_code', 100)->nullable();
            $table->string('result', 32)->index();
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livret_sync_logs');
        Schema::dropIfExists('livret_lessons');
        Schema::dropIfExists('livret_training_contracts');
    }
};
