<?php

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
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
        Schema::create('review_monitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Reservation::class)->unique();
            $table->text('comment')->nullable();
            $table->boolean('is_estimated')->default(false);
            $table->boolean('is_absent')->default(false);
            $table->integer('estimation')->nullable();
            $table->integer('status')->default(SituationStatusEnum::ACTIVE->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_monitors');
    }
};
