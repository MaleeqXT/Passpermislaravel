<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicle_maintenance') && ! Schema::hasColumn('vehicle_maintenance', 'maintenance_kilometer')) {
            Schema::table('vehicle_maintenance', function (Blueprint $table) {
                $table->unsignedInteger('maintenance_kilometer')->default(15000)->after('last_maintenance_mileage');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicle_maintenance') && Schema::hasColumn('vehicle_maintenance', 'maintenance_kilometer')) {
            Schema::table('vehicle_maintenance', function (Blueprint $table) {
                $table->dropColumn('maintenance_kilometer');
            });
        }
    }
};
