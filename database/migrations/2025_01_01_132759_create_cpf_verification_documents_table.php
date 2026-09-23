<?php

use App\Enums\V2\Student\Cpf\DocumentCpfEtatEnum;
use App\Models\Roles\Student\Cpf\Cpf;
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
        Schema::create('cpf_verification_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Cpf::class);
            $table->json('data');
            $table->integer('document')->default(DocumentCpfEtatEnum::questionnaire_entre_formation->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpf_verification_documents');
    }
};
