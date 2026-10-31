<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theory_content_banks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 96)->unique();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        DB::table('theory_content_banks')->insert([
            [
                'id' => '00000000-0000-4000-8000-000000000001',
                'code' => 'legacy',
                'name' => 'Existing Code de la Route Bank',
                'description' => 'Banque historique préservée sans modification de contenu.',
                'status' => 'active',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '00000000-0000-4000-8000-000000000002',
                'code' => 'original-fr-2026',
                'name' => 'Original French Driving Theory 2026',
                'description' => 'Banque originale en préparation éditoriale ; non disponible aux élèves.',
                'status' => 'draft',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('theory_content_banks');
    }
};
