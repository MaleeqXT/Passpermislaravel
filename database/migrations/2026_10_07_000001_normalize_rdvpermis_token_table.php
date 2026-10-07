<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = Schema::hasTable('rdv_permis_tokens');
        $canonical = Schema::hasTable('rdvpermis_tokens');
        if ($legacy && $canonical) {
            // Never guess which encrypted credentials to discard or overwrite.
            throw new RuntimeException('Both RdvPermis token tables exist. Reconcile them before continuing; no records were changed.');
        }
        if ($legacy) {
            Schema::rename('rdv_permis_tokens', 'rdvpermis_tokens');
        }
    }

    public function down(): void
    {
        // Forward-only compatibility repair: retain the canonical table and credentials.
    }
};
