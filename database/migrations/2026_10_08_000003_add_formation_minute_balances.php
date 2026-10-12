<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reservations', 'duration_minutes')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->unsignedSmallInteger('duration_minutes')->nullable()->after('hour');
            });
        }

        if (!Schema::hasColumn('wallets', 'balance_minutes')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->unsignedInteger('balance_minutes')->nullable()->after('balance');
            });
        }

        DB::table('reservations')->whereNull('duration_minutes')->update([
            'duration_minutes' => DB::raw('hour * 60'),
        ]);
        DB::table('wallets')->whereNull('balance_minutes')->update([
            'balance_minutes' => DB::raw('balance * 60'),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'duration_minutes')) {
            Schema::table('reservations', fn (Blueprint $table) => $table->dropColumn('duration_minutes'));
        }
        if (Schema::hasColumn('wallets', 'balance_minutes')) {
            Schema::table('wallets', fn (Blueprint $table) => $table->dropColumn('balance_minutes'));
        }
    }
};