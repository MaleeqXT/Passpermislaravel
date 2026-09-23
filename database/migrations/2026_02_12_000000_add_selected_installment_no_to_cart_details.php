<?php

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
        Schema::table('cart_details', function (Blueprint $table) {
            // Add selected installment number to track which installment was selected
            if (!Schema::hasColumn('cart_details', 'selected_installment_no')) {
                $table->unsignedTinyInteger('selected_installment_no')->nullable()->after('selected_price_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_details', function (Blueprint $table) {
            if (Schema::hasColumn('cart_details', 'selected_installment_no')) {
                $table->dropColumn('selected_installment_no');
            }
        });
    }
};
