<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 20)->default('private');
            // A unique unordered user pair prevents duplicate private threads.
            $table->string('private_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->index(['updated_at', 'id']);
        });
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->unsignedBigInteger('last_read_message_id')->default(0);
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'conversation_id']);
        });
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('client_message_id')->nullable();
            $table->text('message');
            $table->string('message_type', 20)->default('text');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['sender_id', 'client_message_id']);
            $table->index(['conversation_id', 'id']);
            $table->index(['conversation_id', 'read_at', 'sender_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
