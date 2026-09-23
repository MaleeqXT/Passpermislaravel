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
        Schema::create('cars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('marque');
            $table->string('modele');
            $table->string('color');
            $table->string('immatriculation');
            $table->date('date_achat');
            $table->date('date_control_tech');
            $table->date('date_assurance');
            $table->boolean('is_auto')->default(false);
            $table->foreignIdFor(Monitor::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
