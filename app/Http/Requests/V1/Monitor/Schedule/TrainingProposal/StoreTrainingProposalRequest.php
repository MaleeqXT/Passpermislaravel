<?php

namespace App\Http\Requests\V1\Monitor\Schedule\TrainingProposal;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTrainingProposalRequest extends FormRequest
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
            'reservation_id' => 'required|exists:reservations,id',
            'student_id' => 'required|exists:students,id',
            'comment' => 'required|string',
            'status' => ['nullable', new Enum(ProposalStatusEnum::class)],
        ];
    }

    public function messages()
    {
        return [
            'reservation_id.required' => 'Le champ "réservation" est obligatoire.',
            'reservation_id.exists' => 'La réservation spécifiée est invalide.',
            'student_id.exists' => 'L\'élève spécifié est invalide.',
            'comment.required' => 'Le champ "commentaire" est obligatoire.',
            'comment.string' => 'Le champ "commentaire" doit être une chaîne de caractères.',
            'status.enum' => 'Le statut spécifié est invalide.',
        ];
    }
}
