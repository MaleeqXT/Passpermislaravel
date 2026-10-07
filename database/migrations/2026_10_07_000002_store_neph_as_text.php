<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // OpenAPI numeroDossier is a nonempty string without a declared maximum.
            $table->text('neph')->nullable()->change();
        });
    }

    public function down(): void
    {
        // A numeric rollback would destroy leading zeroes and longer identifiers.
    }
};
