<?php

namespace App\Services\LivretNumerique;

/** Local mapping guard; provider search is intentionally unavailable without verified Swagger. */
class SimulatorService
{
    public function mappings(): array { return ['data' => [], 'provider_schema_verified' => false, 'message' => 'La recherche simulateur Livret attend le schéma Swagger actuel.']; }
}
