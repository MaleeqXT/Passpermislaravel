<?php

use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Student\User\Student;
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
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Student::class);
            $table->foreignIdFor(Offer::class);
            $table->integer('balance');
            $table->string('status')->default(WalletTypeEnum::ACTIVE->value);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
