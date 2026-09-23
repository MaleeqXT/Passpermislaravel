<?php

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
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
        Schema::create('document_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Student::class);
            $table->foreignIdFor(Monitor::class);
            $table->foreignIdFor(Reservation::class)->nullable();
            $table->json('data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_evaluations');
    }
};
