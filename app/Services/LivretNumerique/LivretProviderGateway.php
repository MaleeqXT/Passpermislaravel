<?php

namespace App\Services\LivretNumerique;

use App\Models\LivretLesson;
use App\Models\LivretTrainingContract;
use App\Models\User;

/**
 * Deliberate provider boundary.  No Livret route or JSON field is guessed.
 * Add a method implementation only together with a verified Swagger entry in
 * docs/livret-api-contract.md; ApiClient remains the only HTTP transport.
 */
class LivretProviderGateway
{
    public function createTrainingContract(User $user, LivretTrainingContract $contract): never { throw new ProviderSchemaUnavailable; }
    public function deleteTrainingContract(User $user, LivretTrainingContract $contract): never { throw new ProviderSchemaUnavailable; }
    public function createLesson(User $user, LivretLesson $lesson): never { throw new ProviderSchemaUnavailable; }
    public function deleteLesson(User $user, LivretLesson $lesson): never { throw new ProviderSchemaUnavailable; }
    public function findTrainer(User $user, string $providerTrainerId): never { throw new ProviderSchemaUnavailable; }
    public function findSimulator(User $user, string $providerSimulatorId): never { throw new ProviderSchemaUnavailable; }
}
