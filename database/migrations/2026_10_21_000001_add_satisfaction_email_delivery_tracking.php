<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('satisfaction_notifications', function (Blueprint $table) {
            $table->unsignedSmallInteger('email_attempts')->default(0)->after('read_at');
            $table->timestamp('last_email_attempt_at')->nullable()->after('email_attempts');
            $table->timestamp('email_sent_at')->nullable()->after('last_email_attempt_at');
        });
    }

    public function down(): void
    {
        Schema::table('satisfaction_notifications', function (Blueprint $table) {
            $table->dropColumn(['email_attempts', 'last_email_attempt_at', 'email_sent_at']);
        });
    }
};
