<?php

use App\Services\Monitor\Training\ManeuverOccurrenceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maneuver_catalogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 120)->unique();
            $table->string('name', 160);
            $table->string('group_name', 80);
            $table->string('icon', 80)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('maneuver_occurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('maneuver_id')->nullable()->constrained('maneuver_catalogs')->nullOnDelete();
            $table->string('maneuver_slug', 120);
            $table->string('maneuver_name', 160);
            $table->foreignUuid('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('monitor_id')->constrained('monitors')->cascadeOnDelete();
            $table->date('occurred_on')->index();
            $table->unsignedTinyInteger('sequence');
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
            $table->unique(['reservation_id', 'maneuver_slug', 'sequence'], 'maneuver_occurrence_per_lesson');
            $table->index(['student_id', 'monitor_id', 'occurred_on'], 'maneuver_occurrence_student_monitor_date');
        });

        foreach (ManeuverOccurrenceService::DEFAULT_CATALOG as $item) {
            DB::table('maneuver_catalogs')->insert($item + ['id' => (string) Str::uuid(), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maneuver_occurrences');
        Schema::dropIfExists('maneuver_catalogs');
    }
};
