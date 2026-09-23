<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('secretaries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // status will be linked with roles table
            $table->unsignedBigInteger('status')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('status')
                ->references('id')
                ->on('roles')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secretaries');
    }
};
