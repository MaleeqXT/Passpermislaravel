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
        Schema::create('student_training_cancellations', function (Blueprint $table) {
           $table->id();

    // Relations
    $table->uuid('student_id'); // match the UUID type
    $table->foreign('student_id')
          ->references('id')
          ->on('students')
          ->cascadeOnDelete();

 

    // Fields
    $table->integer('hours_requested');
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->text('comment')->nullable();

    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_training_cancellations');
    }
};
