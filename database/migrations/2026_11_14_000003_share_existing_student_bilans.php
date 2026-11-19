<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Existing bilans were already visible in the student portal. Keep
        // that behaviour while making them eligible for the new decision flow.
        DB::table('student_bilans')->whereNull('shared_at')->update([
            'shared_at' => DB::raw('updated_at'),
            'decision_status' => 'pending',
        ]);
    }

    public function down(): void
    {
        // Never erase a student's recorded decision during rollback.
    }
};
