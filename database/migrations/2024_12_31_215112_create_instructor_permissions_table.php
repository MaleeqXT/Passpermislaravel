<?php

use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorDocument;
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
        Schema::create('instructor_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(InstructorDocument::class);
            $table->date('autorisation')->nullable();
            $table->date('visite')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_permissions');
    }
};
