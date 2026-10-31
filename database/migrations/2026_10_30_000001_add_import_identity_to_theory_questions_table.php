<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('theory_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('theory_questions', 'import_source')) {
                $table->string('import_source', 100)->nullable()->after('public_id');
            } else {
                $table->string('import_source', 100)->nullable()->change();
            }
            if (! Schema::hasColumn('theory_questions', 'external_id')) {
                $table->string('external_id', 120)->nullable()->after('import_source');
            } else {
                $table->string('external_id', 120)->nullable()->change();
            }
        });
        Schema::table('theory_questions', function (Blueprint $table) {
            $table->unique(['import_source', 'external_id'], 'theory_questions_import_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('theory_questions', function (Blueprint $table) {
            $table->dropUnique('theory_questions_import_identity_unique');
            $table->dropColumn(['import_source', 'external_id']);
        });
    }
};
