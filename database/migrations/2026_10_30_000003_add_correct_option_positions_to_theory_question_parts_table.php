<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('theory_question_parts', function (Blueprint $table) {
            $table->json('correct_option_positions')->nullable()->after('explanation');
        });
    }

    public function down(): void
    {
        Schema::table('theory_question_parts', function (Blueprint $table) {
            $table->dropColumn('correct_option_positions');
        });
    }
};
