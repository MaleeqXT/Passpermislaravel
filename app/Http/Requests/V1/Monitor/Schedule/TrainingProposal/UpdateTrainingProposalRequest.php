<?php

namespace App\Http\Requests\V1\Monitor\Schedule\TrainingProposal;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTrainingProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'comment' => 'required|string',
            'status' => ['nullable', new Enum(ProposalStatusEnum::class)],
        ];
    }

    public function messages()
    {
        return [
            'comment.required' => 'Le champ "commentaire" est obligatoire.',
            'comment.string' => 'Le champ "commentaire" doit être une chaîne de caractères.',
            'status.enum' => 'Le statut spécifié est invalide.',
        ];
    }
}
