<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->index('monitor_id');
            $table->index('date');
            $table->index('start_at');
            $table->index('end_at');
            // Composite index for the most common filter combo
            $table->index(['monitor_id', 'date', 'start_at', 'end_at'], 'reservations_monitor_date_time_index');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['monitor_id']);
            $table->dropIndex(['date']);
            $table->dropIndex(['start_at']);
            $table->dropIndex(['end_at']);
            $table->dropIndex('reservations_monitor_date_time_index');
        });
    }
};
