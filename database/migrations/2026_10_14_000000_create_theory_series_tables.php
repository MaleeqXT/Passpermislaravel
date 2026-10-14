<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theory_series', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 16)->index(); // training | exam
            $table->string('title', 180);
            $table->string('status', 16)->default('draft')->index(); // draft | published | archived
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'status']);
        });

        Schema::create('theory_series_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('series_id')->constrained('theory_series')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained('theory_questions')->restrictOnDelete();
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
            $table->unique(['series_id', 'question_id']);
            $table->unique(['series_id', 'sort_order']);
        });

        Schema::table('theory_test_attempts', function (Blueprint $table) {
            $table->foreignUuid('series_id')->nullable()->after('student_id')->constrained('theory_series')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->after('started_at')->index();
            $table->index(['student_id', 'series_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('theory_test_attempts', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropIndex(['student_id', 'series_id', 'status']);
            $table->dropColumn(['series_id', 'expires_at']);
        });

        Schema::dropIfExists('theory_series_questions');
        Schema::dropIfExists('theory_series');
    }
};
