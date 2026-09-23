<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('date_expiration_code')->nullable()->after('date_code');
            $table->date('date_expiration_formula')->nullable()->after('date_expiration_code');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['date_expiration_code', 'date_expiration_formula']);
        });
    }
};
