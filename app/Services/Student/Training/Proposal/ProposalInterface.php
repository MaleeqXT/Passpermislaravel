<?php

namespace App\Services\Student\Training\Proposal;

use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\Schedule\TrainingProposal;
use Illuminate\Database\Eloquent\Model;

interface ProposalInterface
{

    /**
     * @param array $attributes
     * @return Training|null|Model
     */
    public function create(array $attributes): TrainingProposal|null|Model;

    public function createMany(array $attributes): mixed;

    /**
     * @param TrainingProposal $trainingProposal
     * @param array $attributes
     * @return bool
     */
    public function update(TrainingProposal $trainingProposal, array $attributes): bool;


    public function updateAndCreatePropositionSession(TrainingProposal $trainingProposal, array $attributes): TrainingProposal|Training|null|Model;

    public function delete(TrainingProposal $trainingProposal): null|Model|int|bool;
}
