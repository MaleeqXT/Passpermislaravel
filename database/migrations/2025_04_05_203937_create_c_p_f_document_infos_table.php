<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('c_p_f_document_infos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('test_pro');
            $table->string('offre')->nullable();
            $table->json('boite')->nullable();
            $table->json('attestation_honneur')->nullable();
            $table->json('reservations')->nullable();
            $table->string('numero_cpf')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('c_p_f_document_infos');
    }
};
