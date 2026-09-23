<?php

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Admin\Area\Lieu;
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
        Schema::create('schedule_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Monitor::class);
            $table->boolean('status')->default(SituationStatusEnum::INACTIVE->value);
            $table->foreignIdFor(Lieu::class);
            $table->json('days');
            $table->date('start_at');
            $table->date('end_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_settings');
    }
};
