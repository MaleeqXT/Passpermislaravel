<?php

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Zone::class);
            $table->foreignIdFor(Student::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_zone');
    }
};
