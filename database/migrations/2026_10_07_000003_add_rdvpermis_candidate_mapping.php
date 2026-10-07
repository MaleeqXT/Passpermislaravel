<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rdvpermis_sync_records', function (Blueprint $table) {
            // Rollback retains data; rerunning this repair must also be safe.
            if (! Schema::hasColumn('rdvpermis_sync_records', 'remote_candidate_id')) {
                $table->text('remote_candidate_id')->nullable();
            }
            if (! Schema::hasColumn('rdvpermis_sync_records', 'permit_group')) {
                $table->string('permit_group', 10)->nullable();
            }
            if (! Schema::hasColumn('rdvpermis_sync_records', 'remote_school_id')) {
                $table->text('remote_school_id')->nullable();
            }
            if (! Schema::hasColumn('rdvpermis_sync_records', 'provider_context')) {
                $table->json('provider_context')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Preserve mappings on rollback; removing them would lose provider identity.
    }
};
