<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The deployed satisfaction tables use MyISAM, which cannot be the
        // referenced side of an InnoDB foreign key. Keep these durable IDs as
        // indexed application-level references instead of forcing an engine
        // conversion or risking existing survey data.
        if (! Schema::hasTable('satisfaction_enrollments')) {
            Schema::create('satisfaction_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('sale_id');
            $table->uuid('offer_id');
            // Snapshot at payment time: never read the mutable wallet/offer balance for a threshold.
            $table->unsignedInteger('purchased_minutes')->default(0);
            $table->boolean('is_first_hour')->default(false);
            $table->boolean('is_driving_package')->default(false);
            $table->timestamps();
            $table->unique(['sale_id', 'offer_id'], 'satisfaction_enrollment_sale_offer_unique');
            $table->index(['student_id', 'offer_id']);
            });
        }

        if (! Schema::hasColumn('trainings', 'satisfaction_enrollment_id')) {
            Schema::table('trainings', fn (Blueprint $table) => $table->uuid('satisfaction_enrollment_id')->nullable()->after('offer_id'));
        }
        $this->addIndexIfMissing('trainings', 'satisfaction_enrollment_id', 'trainings_satisfaction_enrollment_id_index');

        if (! Schema::hasColumn('satisfaction_responses', 'satisfaction_enrollment_id')) {
            Schema::table('satisfaction_responses', fn (Blueprint $table) => $table->uuid('satisfaction_enrollment_id')->nullable()->after('offer_id'));
        }
        $this->addUniqueIfMissing('satisfaction_responses', ['survey_id', 'candidate_id', 'satisfaction_enrollment_id'], 'satisfaction_response_enrollment_unique');
        $this->dropUniqueIfPresent('satisfaction_responses', 'satisfaction_response_student_stage_unique');
        $this->dropUniqueIfPresent('satisfaction_responses', 'satisfaction_response_offer_unique');

        if (! Schema::hasColumn('satisfaction_notifications', 'satisfaction_enrollment_id')) {
            Schema::table('satisfaction_notifications', fn (Blueprint $table) => $table->uuid('satisfaction_enrollment_id')->nullable()->after('offer_id'));
        }
        $this->addUniqueIfMissing('satisfaction_notifications', ['candidate_id', 'survey_id', 'satisfaction_enrollment_id'], 'satisfaction_notification_enrollment_unique');
        $this->dropUniqueIfPresent('satisfaction_notifications', 'satisfaction_notification_student_stage_unique');
        $this->dropUniqueIfPresent('satisfaction_notifications', 'satisfaction_notification_offer_unique');
    }

    public function down(): void
    {
        Schema::table('satisfaction_notifications', function (Blueprint $table) {
            $table->dropUnique('satisfaction_notification_enrollment_unique');
            $table->dropColumn('satisfaction_enrollment_id');
            $table->unique(['candidate_id', 'survey_id'], 'satisfaction_notification_student_stage_unique');
        });
        Schema::table('satisfaction_responses', function (Blueprint $table) {
            $table->dropUnique('satisfaction_response_enrollment_unique');
            $table->dropColumn('satisfaction_enrollment_id');
            $table->unique(['survey_id', 'candidate_id'], 'satisfaction_response_student_stage_unique');
        });
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropIndex('trainings_satisfaction_enrollment_id_index');
            $table->dropColumn('satisfaction_enrollment_id');
        });
        Schema::dropIfExists('satisfaction_enrollments');
    }

    private function dropUniqueIfPresent(string $tableName, string $index): void
    {
        $indexes = Schema::getIndexes($tableName);
        if (! collect($indexes)->contains(fn (array $item) => ($item['name'] ?? null) === $index)) {
            return;
        }
        Schema::table($tableName, fn (Blueprint $table) => $table->dropUnique($index));
    }

    private function addUniqueIfMissing(string $tableName, array $columns, string $index): void
    {
        if (! collect(Schema::getIndexes($tableName))->contains(fn (array $item) => ($item['name'] ?? null) === $index)) {
            Schema::table($tableName, fn (Blueprint $table) => $table->unique($columns, $index));
        }
    }

    private function addIndexIfMissing(string $tableName, string $column, string $index): void
    {
        if (! collect(Schema::getIndexes($tableName))->contains(fn (array $item) => ($item['name'] ?? null) === $index)) {
            Schema::table($tableName, fn (Blueprint $table) => $table->index($column, $index));
        }
    }
};
