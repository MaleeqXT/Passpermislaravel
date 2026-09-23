<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('satisfaction_responses', function (Blueprint $table) { $table->uuid('id')->primary(); $table->foreignUuid('survey_id')->constrained('satisfaction_surveys')->cascadeOnDelete(); $table->foreignUuid('candidate_id')->constrained('students')->cascadeOnDelete(); $table->string('status', 30)->default('started')->index(); $table->string('google_choice', 20)->nullable(); $table->boolean('google_clicked')->default(false); $table->timestamp('google_clicked_at')->nullable(); $table->timestamp('submitted_at')->nullable()->index(); $table->timestamps(); $table->unique(['survey_id','candidate_id']); }); } public function down(): void { Schema::dropIfExists('satisfaction_responses'); } };
