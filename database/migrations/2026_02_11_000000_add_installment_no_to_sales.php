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
        Schema::table('sales', function (Blueprint $table) {
            // Add installment tracking columns
            if (!Schema::hasColumn('sales', 'installment_no')) {
                $table->unsignedTinyInteger('installment_no')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('sales', 'total_tranches')) {
                $table->unsignedTinyInteger('total_tranches')->nullable()->after('installment_no');
            }
            // Add index for faster queries on student and installment
            $table->index(['student_id', 'installment_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'installment_no']);
            if (Schema::hasColumn('sales', 'installment_no')) {
                $table->dropColumn('installment_no');
            }
            if (Schema::hasColumn('sales', 'total_tranches')) {
                $table->dropColumn('total_tranches');
            }
        });
    }
};
