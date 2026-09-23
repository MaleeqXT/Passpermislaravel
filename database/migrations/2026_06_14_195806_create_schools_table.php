<?php

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
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('straight'); // SIRET number
            $table->string('prefectural_approval_number')->nullable();

            $table->string('name');
            $table->string('phone')->nullable();

            $table->text('address');
            $table->string('postal_code', 20);

            $table->string('subscription')->default('Club 300');
            $table->string('qrcode')->nullable();
            $table->string('link')->nullable();
            $table->enum('status', ['Actif', 'Inactif'])
                  ->default('Actif');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
