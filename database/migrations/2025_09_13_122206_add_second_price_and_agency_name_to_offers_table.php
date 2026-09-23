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
        Schema::table('offers', function (Blueprint $table) {
            //
             $table->decimal('second_price', 10, 2)->nullable()->after('discounted_price');
        $table->string('agency_name')->nullable()->after('multi_payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            //
                    $table->dropColumn(['second_price', 'agency_name']);

        });
    }
};
