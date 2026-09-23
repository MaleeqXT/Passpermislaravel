<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rdvpermis_sync_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
           $table->string('entity_type', 100);
$table->string('entity_id', 100);
            $table->string('remote_id', 100)->nullable();
            $table->string('status', 100)->default('pending')->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('last_sync_attempt_at')->nullable();
            $table->unsignedSmallInteger('sync_attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rdvpermis_sync_records');
    }
};
