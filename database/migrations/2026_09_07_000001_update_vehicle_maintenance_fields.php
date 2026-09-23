<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'color')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->string('color')->nullable()->change();
            });
        }

        if (Schema::hasTable('vehicle_maintenance') && Schema::hasColumn('vehicle_maintenance', 'tire_type')) {
            Schema::table('vehicle_maintenance', function (Blueprint $table) {
                $table->dropColumn('tire_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicle_maintenance') && ! Schema::hasColumn('vehicle_maintenance', 'tire_type')) {
            Schema::table('vehicle_maintenance', function (Blueprint $table) {
                $table->string('tire_type')->nullable();
            });
        }
    }
};
