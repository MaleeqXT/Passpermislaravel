<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('satisfaction_answers', function (Blueprint $table) { $table->uuid('id')->primary(); $table->foreignUuid('response_id')->constrained('satisfaction_responses')->cascadeOnDelete(); $table->foreignUuid('question_id')->constrained('satisfaction_questions')->cascadeOnDelete(); $table->unsignedTinyInteger('rating')->nullable(); $table->string('selected_option')->nullable(); $table->text('answer_text')->nullable(); $table->timestamps(); $table->unique(['response_id','question_id']); $table->index('question_id'); }); } public function down(): void { Schema::dropIfExists('satisfaction_answers'); } };
