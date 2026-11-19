<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_bilans', function (Blueprint $table) {
            $table->timestamp('shared_at')->nullable()->after('hours');
            $table->string('decision_status', 16)->default('pending')->after('shared_at');
            $table->timestamp('decision_at')->nullable()->after('decision_status');
            $table->index(['student_id', 'decision_status'], 'student_bilans_student_decision_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('student_bilans', function (Blueprint $table) {
            $table->dropIndex('student_bilans_student_decision_status_index');
            $table->dropColumn(['shared_at', 'decision_status', 'decision_at']);
        });
    }
};
