<?php

use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Student::class);
            $table->foreignIdFor(User::class)->nullable();
            $table->foreignIdFor(Monitor::class)->nullable();
            $table->foreignIdFor(Lieu::class)->nullable();
            $table->boolean('is_auto')->nullable();
            $table->text('comment')->nullable();
            $table->date('date_examen')->nullable();
            $table->time('heure_passage')->nullable();
            $table->string('result_permis')->nullable();
            $table->string('status')->default(ExamenStatusEnum::PENDING->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_exams');
    }
};
