<?php

use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
use App\Models\Roles\Admin\Offer\Cart\Cart;
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
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Student::class);
            $table->foreignIdFor(Cart::class)->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('payment_id')->nullable();
            $table->integer('payment_status')->default(SaleStatusEnum::PENDING->value);
            $table->integer('payment_method')->default(SalePaymentMethodEnum::STRIP->value);
            $table->float('amount');
            $table->integer('balance')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
