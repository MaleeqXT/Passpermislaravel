<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('vehicles')) {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('brand'); $table->string('model'); $table->string('trim')->nullable();
            $table->string('registration_number', 50); $table->date('registration_date');
            $table->string('fuel_type'); $table->string('transmission'); $table->unsignedSmallInteger('power_cv')->nullable();
            $table->unsignedInteger('current_mileage'); $table->string('color')->nullable(); $table->string('photo')->nullable();
            $table->uuid('agency_id')->nullable(); $table->string('status')->default('Disponible');
            $table->uuid('monitor_id')->nullable(); $table->text('notes')->nullable(); $table->timestamps();
        });
        }

        // Keep this migration restartable if MySQL created the base table before
        // a previous DDL statement failed.
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('registration_number', 50)->change();
            $table->unique('registration_number');
            $table->foreign('agency_id')->references('id')->on('zones')->nullOnDelete();
            $table->foreign('monitor_id')->references('id')->on('monitors')->nullOnDelete();
        });

        if (! Schema::hasTable('vehicle_documents')) {
        Schema::create('vehicle_documents', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('vehicle_id'); $table->string('document_type');
            $table->string('file_name'); $table->string('file_path'); $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); $table->boolean('is_required')->default(false); $table->timestamps();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });
        }
        if (! Schema::hasTable('vehicle_maintenance')) {
        Schema::create('vehicle_maintenance', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('vehicle_id')->unique();
            $table->date('last_maintenance_date')->nullable(); $table->date('next_maintenance_date')->nullable();
            $table->unsignedInteger('last_maintenance_mileage')->nullable(); $table->unsignedInteger('maintenance_kilometer')->default(15000); $table->string('maintenance_type')->nullable();
            $table->string('garage')->nullable(); $table->decimal('cost', 10, 2)->nullable();
            $table->string('tire_front_condition')->nullable(); $table->string('tire_rear_condition')->nullable();
            $table->date('tire_change_date')->nullable(); $table->unsignedInteger('tire_change_mileage')->nullable(); $table->text('observations')->nullable(); $table->timestamps();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });
        }
    }
    public function down(): void { Schema::dropIfExists('vehicle_maintenance'); Schema::dropIfExists('vehicle_documents'); Schema::dropIfExists('vehicles'); }
};
