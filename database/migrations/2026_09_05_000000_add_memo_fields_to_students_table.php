<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'memo')) {
            Schema::table('students', function (Blueprint $table) {
                $table->text('memo')->nullable();
            });
        }

        if (! Schema::hasColumn('students', 'memo_color')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('memo_color', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        $columns = array_filter(['memo_color', 'memo'], fn (string $column) => Schema::hasColumn('students', $column));

        if ($columns) {
            Schema::table('students', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
