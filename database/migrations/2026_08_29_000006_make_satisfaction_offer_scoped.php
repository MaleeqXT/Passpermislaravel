<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('satisfaction_responses', function (Blueprint $table) {
            $table->dropUnique('satisfaction_responses_survey_id_candidate_id_unique');
            $table->foreignUuid('offer_id')->nullable()->after('candidate_id')->constrained('offers')->nullOnDelete();
            $table->index('offer_id');
            $table->unique(['survey_id', 'candidate_id', 'offer_id'], 'satisfaction_response_offer_unique');
        });

        Schema::table('satisfaction_notifications', function (Blueprint $table) {
            $table->dropUnique('satisfaction_notifications_candidate_id_type_unique');
            $table->foreignUuid('offer_id')->nullable()->after('candidate_id')->constrained('offers')->nullOnDelete();
            $table->foreignUuid('survey_id')->nullable()->after('offer_id')->constrained('satisfaction_surveys')->cascadeOnDelete();
            $table->unique(['candidate_id', 'offer_id', 'survey_id'], 'satisfaction_notification_offer_unique');
        });
    }

    public function down(): void
    {
        Schema::table('satisfaction_notifications', function (Blueprint $table) {
            $table->dropUnique('satisfaction_notification_offer_unique');
            $table->dropConstrainedForeignId('survey_id');
            $table->dropConstrainedForeignId('offer_id');
            $table->unique(['candidate_id', 'type']);
        });
        Schema::table('satisfaction_responses', function (Blueprint $table) {
            $table->dropUnique('satisfaction_response_offer_unique');
            $table->dropConstrainedForeignId('offer_id');
            $table->unique(['survey_id', 'candidate_id']);
        });
    }
};
