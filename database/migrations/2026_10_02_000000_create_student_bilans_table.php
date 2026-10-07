<?php

use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_bilans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('monitor_id')->nullable()->constrained('monitors')->nullOnDelete();
            $table->json('evaluations')->nullable();
            $table->json('hours')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_bilans');
    }
};
