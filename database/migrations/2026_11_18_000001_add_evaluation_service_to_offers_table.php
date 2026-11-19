<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separates the two pre-training services the client sells:
 *  - "conduite": Évaluation de conduite (initial driving assessment)
 *  - "bilan":    Bilan de compétences (assessment for learners with experience)
 *
 * `is_evaluation` cannot be reused: it marks every formula that includes an
 * evaluation hour, not the standalone service itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('offers', 'evaluation_service')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->string('evaluation_service', 20)->nullable()->after('is_evaluation')->index();
            });
        }

        // The two existing standalone evaluation-hour products are the
        // "Évaluation de conduite" service. No Bilan product exists yet; the
        // administrator creates it from the offer form.
        DB::table('offers')
            ->whereNull('evaluation_service')
            ->where('name', 'like', '%Heure d%valuation%')
            ->update(['evaluation_service' => 'conduite']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('offers', 'evaluation_service')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->dropIndex(['evaluation_service']);
                $table->dropColumn('evaluation_service');
            });
        }
    }
};
