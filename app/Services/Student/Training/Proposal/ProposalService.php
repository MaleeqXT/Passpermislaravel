<?php

namespace App\Services\Student\Training\Proposal;


use App\Enums\V2\Admin\Popular\CodeErrorsEnum;
use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\Schedule\TrainingProposal;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\EditTrainingProposalRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\FetchAllReservationInTrainingProposalRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\FetchReservationTrainingProposalRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\FetchTrainingInProposalRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\StoreManyTrainingProposalRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\TrainingProposal\StoreTrainingProposalRepo;
use App\Services\Student\Training\info\TrainingInterface;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ProposalService implements ProposalInterface
{
    public function __construct(public TrainingInterface $service) {}

    /**
     * Create many training proposals.
     *
     * @param array $attributes
     * @return Training|null|Model
     * @throws Exception
     */
    public function createMany(array $attributes): Training|null|Model
    {
        $this->checkReservationStatus($attributes['data']);

        return StoreManyTrainingProposalRepo::run($attributes['data']);
    }

    /**
     * Check if any reservation is cancelled.
     *
     * @param array $propositions
     * @throws Exception
     */
    protected function checkReservationStatus(array $data): void
    {
        $resevationsIds = collect($data)->pluck('reservation_id')->toArray();
        $reservation = FetchAllReservationInTrainingProposalRepo::run($resevationsIds, ProposalStatusEnum::CANCELLED->value);

        if (!$reservation) {
            $this->flashErrorAndThrowException(CodeErrorsEnum::PLANNING_HAS_SESSION_OR_HAS_ALREADY_PROPOSITION->value);
        }
    }

    /**
     * Flash error message and throw exception.
     *
     * @param string $errorMessage
     * @throws Exception
     */
    protected function flashErrorAndThrowException(string $errorMessage): void
    {
        session()->flash('error', $errorMessage);
        throw new Exception($errorMessage);
    }

    /**
     * Update and create proposition session.
     *
     * @param TrainingProposal $trainingProposal
     * @param array $attributes
     * @return TrainingProposal|Training|Model|null
     * @throws Exception
     */


    public function updateAndCreatePropositionSession(TrainingProposal $trainingProposal, array $attributes): TrainingProposal|Model|null
    {
        $status = (int) $attributes['status'];

        if ($trainingProposal->status === ProposalStatusEnum::RESERVER->value) {
            $this->flashErrorAndThrowException(CodeErrorsEnum::PROPOSITION_HAS_ALREADY_ACTIVATED->value);
        }

        $reservation = $trainingProposal->reservation;
        abort_unless($reservation, 422, 'Le créneau de cette proposition est introuvable.');

        if ($status === ProposalStatusEnum::RESERVER->value) {
            abort_if($reservation->training()->exists(), 422, 'Ce créneau est déjà réservé.');

            $wallet = $trainingProposal->student
                ->wallets()
                ->where('offer_id', $attributes['offer_id'])
                ->where('status', WalletTypeEnum::ACTIVE->value)
                ->lockForUpdate()
                ->first();

            abort_unless($wallet, 422, 'Le forfait sélectionné n’est pas actif pour cet élève.');
        }

        $trainingProposal->update([
            'status' => $status,
            'comment' => $attributes['comment'] ?? null,
        ]);

        if ($status === ProposalStatusEnum::RESERVER->value) {
            $this->service->create([
                'student_id' => $trainingProposal->student_id,
                'offer_id' => $attributes['offer_id'],
                'session_type' => 'Prestation',
            ], $reservation);

            // The chosen proposal is the reservation. Any offer made to a
            // different student for that same availability is no longer valid.
            TrainingProposal::query()
                ->where('reservation_id', $reservation->id)
                ->whereKeyNot($trainingProposal->id)
                ->update(['status' => ProposalStatusEnum::CANCELLED->value]);
        }

        return $trainingProposal->fresh();
    }


    /**
     * Update a training proposal.
     *
     * @param TrainingProposal $trainingProposal
     * @param array $attributes
     * @return bool
     * @throws Exception
     */
    public function update(TrainingProposal $trainingProposal, array $attributes): bool
    {
        $this->checkProposalStatus($trainingProposal, $attributes);

        $attributes['reservation_id'] = $trainingProposal->reservation_id;
        $attributes['reservation'] = $trainingProposal->reservation;

        if (!$this->isValidReservation($attributes)) {
            $this->flashErrorAndThrowException(CodeErrorsEnum::PLANNING_HAS_SESSION_OR_HAS_ALREADY_PROPOSITION->value);
        }

        return EditTrainingProposalRepo::run($trainingProposal, Arr::only($attributes, ['status']));
    }

       protected function createPropositionSession(TrainingProposal $trainingProposal, array $attributes): void
    {
        // Your logic for creating proposition session
        // This is just an example - implement your actual business logic
        // Example: Create a training session linked to this proposal
    }


    /**
     * Check if the proposal status is valid.
     *
     * @param TrainingProposal $trainingProposal
     * @param array $attributes
     * @throws Exception
     */
    protected function checkProposalStatus(TrainingProposal $trainingProposal, array $attributes): void
    {
        if ($trainingProposal->status == ProposalStatusEnum::RESERVER->value) {
            $this->flashErrorAndThrowException(CodeErrorsEnum::PROPOSITION_HAS_ALREADY_ACTIVATED->value);
        }
    }

    /**
     * Check if the reservation is valid.
     *
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection
     */
    protected function isValidReservation(array $attributes)
    {
        return FetchReservationTrainingProposalRepo::run($attributes, ProposalStatusEnum::RESERVER->value, '!=');
    }

    /**
     * Create a training proposal.
     *
     * @param array $attributes
     * @return Training|null|Model
     * @throws Exception
     */
    public function create(array $attributes): Training|null|Model
    {
        $this->checkReservationStatus([$attributes]);

        return StoreTrainingProposalRepo::run($attributes);
    }

    /**
     * Delete a training proposal.
     *
     * @param TrainingProposal $trainingProposal
     * @return Model|int|bool|null
     * @throws Exception
     */
    public function delete(TrainingProposal $trainingProposal): null|Model|int|bool
    {
        if ($trainingProposal->status != ProposalStatusEnum::PENDING->value) {
            $this->flashErrorAndThrowException(CodeErrorsEnum::PROPOSITION_HAS_ALREADY_ACTIVATED->value);
        }

        return $trainingProposal->delete();
    }
}
