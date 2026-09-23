<?php

use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Competency\Competency;
use App\Models\Roles\Student\User\Student;
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
        Schema::create('ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Competency::class)->onDelete('cascade');
            $table->integer('rating')->nullable();
            $table->text('comment')->nullable();
            $table->foreignIdFor(Student::class);
            $table->foreignIdFor(Monitor::class);
            $table->unique(['Competency_id', 'student_id', 'monitor_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
