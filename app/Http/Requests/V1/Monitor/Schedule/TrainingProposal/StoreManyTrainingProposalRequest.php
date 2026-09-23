<?php

namespace App\Http\Requests\V1\Monitor\Schedule\TrainingProposal;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreManyTrainingProposalRequest extends FormRequest
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
            'data' => 'required|array',
            'data.*.reservation_id' => 'required|exists:reservations,id',
            'data.*.comment' => 'nullable|string',
            'data.*.student_id' => 'nullable|exists:students,id',
            'data.*.status' => ['nullable', new Enum(ProposalStatusEnum::class)],
        ];
    }

    public function messages()
    {
        return [
            'data.*.reservation_id.required' => 'Le champ "réservation" est obligatoire.',
            'data.*.reservation_id.exists' => 'La réservation spécifiée est invalide.',
            'data.*.student_id.exists' => 'L\'élève spécifié est invalide.',
            'data.*.comment.string' => 'Le champ "commentaire" doit être une chaîne de caractères.',
        ];
    }
}
