<?php

namespace App\Http\Requests\V1\Student\Schedule\Training;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class UpdateTrainingProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(ProposalStatusEnum::class)],
            'comment' => 'nullable|string|max:2000',
            'offer_id' => [
                Rule::requiredIf(fn () => (int) $this->input('status') === ProposalStatusEnum::RESERVER->value),
                'nullable',
                'uuid',
                'exists:offers,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire',
            'status.enum' => 'Le statut sélectionné n\'est pas valide',
            'offer_id.required' => 'Sélectionnez un forfait actif pour réserver cette séance.',
            'offer_id.exists' => 'Le forfait sélectionné est invalide.',
           
            'comment.string' => 'Le commentaire doit être une chaîne de caractères',
            'comment.max' => 'Le commentaire ne peut pas dépasser 2000 caractères',
        ];
    }
}
