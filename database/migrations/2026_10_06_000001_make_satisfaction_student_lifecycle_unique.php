<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // Superseded by the enrolment-scoped lifecycle migration. Do not
        // collapse or delete historical answers/notifications here.
    }

    public function down(): void
    {
        // No schema change was made by this compatibility migration.
    }
};
