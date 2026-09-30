<?php

use App\Models\Roles\Student\User\Competency\MainCompetency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([14, 10, 10, 7] as $index => $questionCount) {
            $main = MainCompetency::query()
                ->where('label', 'C' . ($index + 1))
                ->orderByDesc('updated_at')
                ->first();

            if (!$main) {
                continue;
            }

            // The legacy model casts status to boolean. Update the integer
            // reference status directly so the active-question query receives 1.
            DB::table('competencies')
                ->where('main_competency_id', $main->id)
                ->whereBetween('position', [1, $questionCount])
                ->update(['status' => 1]);
        }
    }

    public function down(): void
    {
        // Reference questions remain active on rollback.
    }
};
