<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const LEGACY_BANK_ID = '00000000-0000-4000-8000-000000000001';

    public function up(): void
    {
        Schema::table('theory_questions', function (Blueprint $table) {
            $table->foreignUuid('content_bank_id')->nullable()->after('id')->constrained('theory_content_banks')->nullOnDelete();
            $table->string('content_origin', 96)->nullable()->after('external_id');
            $table->string('license_reference', 2048)->nullable()->after('content_origin');
            $table->string('review_status', 32)->nullable()->after('requires_review')->index();
            $table->index(['content_bank_id', 'status', 'is_active'], 'theory_questions_bank_delivery_index');
        });

        Schema::table('theory_series', function (Blueprint $table) {
            $table->foreignUuid('content_bank_id')->nullable()->after('id')->constrained('theory_content_banks')->nullOnDelete();
            $table->index(['content_bank_id', 'status'], 'theory_series_bank_status_index');
        });

        Schema::table('theory_import_batches', function (Blueprint $table) {
            $table->foreignUuid('content_bank_id')->nullable()->after('id')->constrained('theory_content_banks')->nullOnDelete();
            $table->index('content_bank_id');
        });

        // This is the only legacy data update: it establishes ownership without
        // touching question text, options, media, attempt snapshots, or status.
        DB::table('theory_questions')->whereNull('content_bank_id')->update(['content_bank_id' => self::LEGACY_BANK_ID]);
        DB::table('theory_series')->whereNull('content_bank_id')->update(['content_bank_id' => self::LEGACY_BANK_ID]);
        DB::table('theory_import_batches')->whereNull('content_bank_id')->update(['content_bank_id' => self::LEGACY_BANK_ID]);
    }

    public function down(): void
    {
        Schema::table('theory_import_batches', function (Blueprint $table) {
            $table->dropForeign(['content_bank_id']);
            $table->dropIndex(['content_bank_id']);
            $table->dropColumn('content_bank_id');
        });

        Schema::table('theory_series', function (Blueprint $table) {
            $table->dropForeign(['content_bank_id']);
            $table->dropIndex('theory_series_bank_status_index');
            $table->dropColumn('content_bank_id');
        });

        Schema::table('theory_questions', function (Blueprint $table) {
            $table->dropForeign(['content_bank_id']);
            $table->dropIndex('theory_questions_bank_delivery_index');
            $table->dropIndex(['review_status']);
            $table->dropColumn(['content_bank_id', 'content_origin', 'license_reference', 'review_status']);
        });
    }
};
