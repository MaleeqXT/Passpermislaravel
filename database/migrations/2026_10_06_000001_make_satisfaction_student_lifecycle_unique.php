<?php

use App\Models\SatisfactionAnswer;
use App\Models\SatisfactionNotification;
use App\Models\SatisfactionResponse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Older releases allowed one response per offer. Keep the most useful
        // response before enforcing the student-level lifecycle uniqueness.
        SatisfactionResponse::query()->get()->groupBy(fn ($response) => $response->survey_id.'|'.$response->candidate_id)
            ->each(function ($responses) {
                if ($responses->count() < 2) return;
                $keep = $responses->sortByDesc(fn ($response) => ($response->status === 'completed' ? '1' : '0').($response->submitted_at ?? $response->created_at))->first();
                $responses->where('id', '!=', $keep->id)->each(function ($duplicate) {
                    SatisfactionNotification::query()->where('response_id', $duplicate->id)->delete();
                    SatisfactionAnswer::query()->where('response_id', $duplicate->id)->delete();
                    $duplicate->delete();
                });
            });

        Schema::table('satisfaction_responses', function (Blueprint $table) {
            $table->dropUnique('satisfaction_response_offer_unique');
            $table->unique(['survey_id', 'candidate_id'], 'satisfaction_response_student_stage_unique');
        });

        Schema::table('satisfaction_notifications', function (Blueprint $table) {
            $table->dropUnique('satisfaction_notification_offer_unique');
            $table->unique(['candidate_id', 'survey_id'], 'satisfaction_notification_student_stage_unique');
        });
    }

    public function down(): void
    {
        Schema::table('satisfaction_notifications', function (Blueprint $table) {
            $table->dropUnique('satisfaction_notification_student_stage_unique');
            $table->unique(['candidate_id', 'offer_id', 'survey_id'], 'satisfaction_notification_offer_unique');
        });
        Schema::table('satisfaction_responses', function (Blueprint $table) {
            $table->dropUnique('satisfaction_response_student_stage_unique');
            $table->unique(['survey_id', 'candidate_id', 'offer_id'], 'satisfaction_response_offer_unique');
        });
    }
};
