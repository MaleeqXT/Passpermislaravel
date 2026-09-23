<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('satisfaction_questions', function (Blueprint $table) { $table->uuid('id')->primary(); $table->foreignUuid('survey_id')->constrained('satisfaction_surveys')->cascadeOnDelete(); $table->string('section')->nullable(); $table->text('question'); $table->string('type'); $table->json('options')->nullable(); $table->boolean('is_required')->default(false); $table->unsignedInteger('sort_order')->default(0); $table->boolean('is_active')->default(true); $table->timestamps(); $table->index(['survey_id','sort_order']); }); } public function down(): void { Schema::dropIfExists('satisfaction_questions'); } };
