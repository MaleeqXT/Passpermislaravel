<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignUuid('preferred_monitor_id')
                ->nullable()
                ->after('aval_monitor')
                ->constrained('monitors')
                ->nullOnDelete();
            $table->string('app_language', 10)->nullable()->after('preferred_monitor_id');
            $table->json('communication_preferences')->nullable()->after('app_language');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['preferred_monitor_id']);
            $table->dropColumn(['preferred_monitor_id', 'app_language', 'communication_preferences']);
        });
    }
};
