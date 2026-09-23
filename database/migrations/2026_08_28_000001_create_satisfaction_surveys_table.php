<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('satisfaction_surveys', function (Blueprint $table) { $table->uuid('id')->primary(); $table->string('name', 180); $table->string('slug', 80)->unique(); $table->string('stage', 40)->index(); $table->text('description')->nullable(); $table->boolean('is_active')->default(true)->index(); $table->timestamps(); }); } public function down(): void { Schema::dropIfExists('satisfaction_surveys'); } };
