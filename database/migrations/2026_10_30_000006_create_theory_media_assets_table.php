<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theory_media_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_bank_id')->constrained('theory_content_banks')->cascadeOnDelete();
            $table->foreignUuid('question_id')->nullable()->constrained('theory_questions')->nullOnDelete();
            $table->string('kind', 16); // image | video | audio
            // 191 UTF-8 characters fit this database's 1000-byte index limit
            // and exceed the storage paths used by this application.
            $table->string('path', 191)->unique();
            $table->string('original_path', 2048)->nullable();
            $table->text('creative_brief')->nullable();
            $table->string('creation_method', 120);
            $table->string('provenance', 2048);
            $table->char('file_hash', 64);
            $table->string('editorial_review_status', 32)->default('pending')->index();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index(['content_bank_id', 'question_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theory_media_assets');
    }
};
