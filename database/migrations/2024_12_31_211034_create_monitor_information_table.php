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
        Schema::create('monitor_information', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Monitor::class);
            $table->string('experience')->default(0);
            $table->string('dernier_experience')->nullable();
            $table->string('details_experience')->nullable();
            $table->string('is_manual')->default(false);
            $table->string('is_auto')->default(false);
            $table->string('zone_souhaitee')->nullable();
            $table->string('departement')->nullable();
            $table->string('numero_autorisation')->nullable();
            $table->string('tarif_enseignement')->nullable();
            $table->string('tarif_car')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_information');
    }
};
