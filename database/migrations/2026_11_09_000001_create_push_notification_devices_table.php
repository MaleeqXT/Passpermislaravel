<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('push_notification_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
          $table->string('fcm_token', 250)->unique();
            $table->string('device_name', 120)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('browser', 80)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'enabled']);
        });

        Schema::create('push_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('push_notification_device_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('error_code', 80)->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['message_id', 'push_notification_device_id'], 'push_delivery_message_device_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_deliveries');
        Schema::dropIfExists('push_notification_devices');
    }
};
