<?php

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeStatusEnum;
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
        Schema::create('offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->boolean('is_cpf')->default(false);
            $table->boolean('is_offer_cart')->default(false);
            $table->longText('description')->nullable();
            $table->longText('caracteristiques')->nullable();
            $table->json('options')->nullable();
            // prices gestion
            $table->float('price_ht')->nullable();
            $table->float('original_price')->nullable();
            $table->float('discounted_price')->nullable();
            $table->float('final_price')->nullable();

            $table->integer('balance')->nullable();
            $table->boolean('is_auto')->default(false);
            $table->integer('multi_payment')->nullable();
            $table->string('type')->default(OffreTypeStatusEnum::CODE->value);
            $table->string('type_offre')->default(OffreTypeEnum::FORFAIT->value);
            $table->string('status')->default(SituationStatusEnum::ACTIVE->value);
            $table->string('color')->default('black');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
