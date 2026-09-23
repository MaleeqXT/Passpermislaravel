<?php

use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Models\Roles\Student\Schedule\Training;
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
        Schema::create('cancellations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Training::class);
            $table->boolean('is_justified')->default(false);
            $table->integer('status')->default(CancellationStatusEnum::PENDING);
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellations');
    }
};
