<?php

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
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Monitor::class);
            $table->date('date');
            $table->time('start_at');
            $table->time('end_at');
            $table->integer('hour')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignIdFor(Lieu::class);
            $table->string('color')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
