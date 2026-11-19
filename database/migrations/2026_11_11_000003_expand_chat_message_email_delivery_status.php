<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_message_email_deliveries', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('chat_message_email_deliveries', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
