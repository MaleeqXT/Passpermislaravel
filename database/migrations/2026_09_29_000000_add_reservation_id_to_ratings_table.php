<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropUnique('ratings_competency_id_student_id_monitor_id_unique');
            $table->foreignUuid('reservation_id')->nullable()->after('monitor_id')->constrained('reservations')->nullOnDelete();
            $table->unique(['Competency_id', 'student_id', 'monitor_id', 'reservation_id'], 'ratings_competency_student_monitor_reservation_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
            $table->dropUnique('ratings_competency_student_monitor_reservation_unique');
            $table->dropColumn('reservation_id');
            $table->unique(['Competency_id', 'student_id', 'monitor_id']);
        });
    }
};
