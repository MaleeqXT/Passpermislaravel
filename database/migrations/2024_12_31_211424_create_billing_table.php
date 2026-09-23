<?php

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
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
        Schema::create('billings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Monitor::class);
            $table->string('num_facture')->nullable('AD-');
            $table->date('from');
            $table->date('to');
            $table->date('date_paiement')->nullable();
            $table->decimal('montant')->default(0);
            $table->json('details');
            $table->string('status')->default(SituationStatusEnum::ACTIVE->value);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_facturations');
    }
};
