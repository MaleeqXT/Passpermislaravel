<?php

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Student\User\Competency\MainCompetency;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([14, 10, 10, 7] as $index => $questionCount) {
            $main = MainCompetency::query()
                ->where('position', $index + 1)
                ->orderByDesc('updated_at')
                ->first();

            if (!$main) {
                continue;
            }

            // Older seed data may contain several questions with position 1.
            // Archive all of them first, then reactivate the current reference
            // question for each required position. Ratings remain untouched.
            $main->competencies()->update(['status' => SituationStatusEnum::INACTIVE->value]);

            for ($position = 1; $position <= $questionCount; $position++) {
                $main->competencies()
                    ->where('position', $position)
                    ->orderByDesc('updated_at')
                    ->first()
                    ?->update(['status' => SituationStatusEnum::ACTIVE->value]);
            }
        }
    }

    public function down(): void
    {
        // Archived legacy questions intentionally remain archived on rollback.
    }
};
