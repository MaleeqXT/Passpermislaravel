<?php

use App\Enums\V2\Student\Cpf\EleveCpfStatusEnum;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\User;
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
        Schema::create('cpfs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(\App\Models\Roles\Student\User\Student::class);
            $table->foreignIdFor(User::class);
            $table->date('start_at');
            $table->date('end_at');
            $table->foreignIdFor(Offer::class);
            $table->date('date_verif')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default(EleveCpfStatusEnum::Accepte->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpfs');
    }
};
