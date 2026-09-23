<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('satisfaction_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('candidate_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('response_id')->constrained('satisfaction_responses')->cascadeOnDelete();
            $table->string('type', 60);
            $table->string('title', 160);
            $table->text('message');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['candidate_id', 'type']);
            $table->unique('response_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_notifications');
    }
};
