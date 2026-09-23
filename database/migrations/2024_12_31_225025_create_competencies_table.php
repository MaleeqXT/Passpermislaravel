<?php

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Student\User\Competency\MainCompetency;
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
        Schema::create('competencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('label');
            $table->integer('status')->default(SituationStatusEnum::ACTIVE->value);
            $table->foreignIdFor(MainCompetency::class)->onDelete('cascade');
            $table->integer('position')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competencies');
    }
};
