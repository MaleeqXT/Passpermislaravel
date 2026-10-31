<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Main competencies are scoped by agency zone in the API, but the original
     * table migration omitted this column. This makes fresh and live databases
     * consistent with the queries used by the skills endpoints.
     */
    public function up(): void
    {
        if (! Schema::hasTable('main_competencies') || Schema::hasColumn('main_competencies', 'zone_id')) {
            return;
        }

        Schema::table('main_competencies', function (Blueprint $table) {
            $table->uuid('zone_id')->nullable()->index()->after('id');
        });
    }

    /**
     * Kept intentionally non-destructive: an existing live database may have
     * acquired this column outside this migration.
     */
    public function down(): void
    {
    }
};
