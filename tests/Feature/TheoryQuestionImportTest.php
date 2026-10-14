<?php

namespace Tests\Feature;

use App\Models\Theory\TheoryCategory;
use App\Models\Theory\TheoryImportBatch;
use App\Models\Theory\TheoryQuestion;
use App\Services\Theory\TheoryQuestionImporter;
use Database\Seeders\Theory\OriginalTheoryQuestionBatch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TheoryQuestionImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('theory_answer_options');
        Schema::dropIfExists('theory_questions');
        Schema::dropIfExists('theory_import_batches');
        Schema::dropIfExists('theory_categories');

        Schema::create('theory_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('theory_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('public_id')->unique();
            $table->uuid('category_id');
            $table->text('question_text');
            $table->string('difficulty');
            $table->string('answer_type');
            $table->string('media_type')->nullable();
            $table->string('media_path')->nullable();
            $table->text('explanation');
            $table->string('official_reference')->nullable();
            $table->string('status');
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_review')->default(false);
            $table->text('review_notes')->nullable();
            $table->char('content_hash', 64)->unique();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('theory_answer_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('question_id');
            $table->string('label');
            $table->boolean('is_correct');
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });
        Schema::create('theory_import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_code')->unique();
            $table->string('source');
            $table->unsignedInteger('target_total')->default(4950);
            $table->unsignedInteger('source_count')->default(0);
            $table->unsignedInteger('inserted_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_initial_batch_is_resumable_and_does_not_duplicate_questions(): void
    {
        $importer = app(TheoryQuestionImporter::class);
        $categories = OriginalTheoryQuestionBatch::categories();
        $questions = OriginalTheoryQuestionBatch::questions();

        $first = $importer->import('test-original-batch', $categories, $questions, 20);
        $this->assertSame(20, $first['inserted_now']);
        $this->assertFalse($first['completed']);
        $this->assertDatabaseCount('theory_categories', 10);
        $this->assertDatabaseCount('theory_questions', 20);

        $second = $importer->import('test-original-batch', $categories, $questions, 100);
        $this->assertTrue($second['completed']);
        $this->assertSame(count($questions), TheoryQuestion::count());
        $this->assertSame(60, TheoryQuestion::where('status', 'published')->where('is_active', true)->count());
        $this->assertGreaterThanOrEqual(1, TheoryQuestion::where('answer_type', 'multiple')->count());
        $this->assertSame(1, TheoryImportBatch::count());
        $this->assertSame('completed', TheoryImportBatch::first()->status);
        $this->assertSame(10, TheoryCategory::count());
    }
}
