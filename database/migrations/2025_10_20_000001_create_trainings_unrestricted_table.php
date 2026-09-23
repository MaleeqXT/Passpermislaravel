<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    if (!Schema::hasTable('trainings_unrestricted')) {
        Schema::create('trainings_unrestricted', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // ✅ both Student and Offer are UUID-based models
            $table->uuid('student_id');
            $table->uuid('reservation_id');
            $table->uuid('offer_id');

            // ✅ foreign keys
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('reservation_id')->references('id')->on('reservations')->cascadeOnDelete();
            $table->foreign('offer_id')->references('id')->on('offers')->cascadeOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings_unrestricted');
    }
};
