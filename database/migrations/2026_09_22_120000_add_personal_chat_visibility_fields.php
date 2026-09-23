<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->timestamp('cleared_at')->nullable()->after('last_read_message_id');
            $table->timestamp('hidden_at')->nullable()->after('cleared_at');
            $table->index(['user_id', 'hidden_at']);
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'hidden_at']);
            $table->dropColumn(['cleared_at', 'hidden_at']);
        });
    }
};
