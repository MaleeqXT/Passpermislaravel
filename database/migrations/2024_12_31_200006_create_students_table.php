<?php

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Enums\V2\Student\User\StudentVitalBoxTypeEnum;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->integer('balance')->default(0);
            $table->bigInteger('neph')->nullable();
            $table->boolean('is_cpf')->default(false);
            $table->integer('boite_type')->default(StudentVitalBoxTypeEnum::Manual->value);
            $table->date('date_code')->nullable();
            $table->string('how_know')->nullable();
            $table->integer('status')->default(SituationStatusEnum::ACTIVE->value);
            // $table->integer('frequence')->nullable();
            $table->string('contract_path')->nullable();
            $table->boolean('aval_monitor')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
