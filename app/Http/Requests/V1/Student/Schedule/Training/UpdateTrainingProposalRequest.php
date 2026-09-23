<?php

namespace App\Http\Requests\V1\Student\Schedule\Training;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire',
            'status.enum' => 'Le statut sélectionné n\'est pas valide',
           
            'comment.string' => 'Le commentaire doit être une chaîne de caractères',
            'comment.max' => 'Le commentaire ne peut pas dépasser 2000 caractères',
        ];
    }
}
