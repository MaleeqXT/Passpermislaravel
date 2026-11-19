<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_proposals', function (Blueprint $table) {
            $table->unique(['reservation_id', 'student_id'], 'training_proposals_reservation_student_unique');
        });
    }

    public function down(): void
    {
        Schema::table('training_proposals', function (Blueprint $table) {
            $table->dropUnique('training_proposals_reservation_student_unique');
        });
    }
};
