<?php

use App\Models\Roles\Monitor\User\Monitor;
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
        Schema::create('instructor_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Monitor::class);
            $table->string('denomination_social')->nullable();
            $table->string('forme_juridique')->nullable();
            $table->date('date_creation')->nullable();
            $table->string('siret')->nullable();
            $table->string('num_autorisation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_documents');
    }
};
