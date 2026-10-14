<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theory_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('name', 140);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('theory_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('public_id', 32)->unique();
            $table->foreignUuid('category_id')->constrained('theory_categories')->restrictOnDelete();
            $table->text('question_text');
            $table->string('difficulty', 16)->default('medium')->index();
            $table->string('answer_type', 16)->default('single');
            $table->string('media_type', 16)->nullable();
            $table->string('media_path', 2048)->nullable();
            $table->text('explanation');
            $table->string('official_reference', 2048)->nullable();
            $table->string('status', 16)->default('draft')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('requires_review')->default(false)->index();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->char('content_hash', 64)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category_id', 'status', 'is_active']);
        });

        Schema::create('theory_answer_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained('theory_questions')->cascadeOnDelete();
            $table->string('label', 1000);
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['question_id', 'sort_order']);
        });

        Schema::create('theory_test_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('mode', 24)->default('mock');
            $table->string('status', 24)->default('in_progress')->index();
            $table->unsignedTinyInteger('total_questions')->default(40);
            $table->unsignedTinyInteger('pass_threshold')->default(35);
            $table->unsignedTinyInteger('correct_answers')->nullable();
            $table->unsignedTinyInteger('incorrect_answers')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();
            $table->index(['student_id', 'status']);
        });

        Schema::create('theory_attempt_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('theory_test_attempts')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained('theory_questions')->restrictOnDelete();
            $table->unsignedTinyInteger('sort_order');
            $table->json('question_snapshot');
            $table->json('selected_option_ids')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['attempt_id', 'question_id']);
            $table->unique(['attempt_id', 'sort_order']);
        });

        Schema::create('theory_student_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('theory_categories')->cascadeOnDelete();
            $table->unsignedInteger('questions_answered')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->timestamp('last_answered_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'category_id']);
        });

        Schema::create('theory_import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_code', 96)->unique();
            $table->string('source', 160);
            $table->unsignedInteger('target_total')->default(4950);
            $table->unsignedInteger('source_count')->default(0);
            $table->unsignedInteger('inserted_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->string('status', 24)->default('pending')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theory_import_batches');
        Schema::dropIfExists('theory_student_progress');
        Schema::dropIfExists('theory_attempt_answers');
        Schema::dropIfExists('theory_test_attempts');
        Schema::dropIfExists('theory_answer_options');
        Schema::dropIfExists('theory_questions');
        Schema::dropIfExists('theory_categories');
    }
};
