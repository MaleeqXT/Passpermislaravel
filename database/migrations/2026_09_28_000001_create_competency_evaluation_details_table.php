<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_evaluation_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rating_id')->unique();
            $table->boolean('student_evaluation')->default(false);
            $table->string('active_tab')->default('pourquoi');
            $table->text('student_response')->nullable();
            $table->timestamps();

            $table->foreign('rating_id')->references('id')->on('ratings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_evaluation_details');
    }
};
