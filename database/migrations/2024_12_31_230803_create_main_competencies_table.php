<?php

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
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
        Schema::create('main_competencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('status')->default(SituationStatusEnum::ACTIVE->value);
            $table->string('name');
            $table->integer('position')->default(1);
            $table->string('label');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_competencies');
    }
};
