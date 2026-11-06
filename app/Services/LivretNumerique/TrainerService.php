<?php

namespace App\Services\LivretNumerique;

/** Local mapping guard; provider search is intentionally unavailable without verified Swagger. */
class TrainerService
{
    public function mappings(): array { return ['data' => [], 'provider_schema_verified' => false, 'message' => 'La recherche formateur Livret attend le schéma Swagger actuel.']; }
}
