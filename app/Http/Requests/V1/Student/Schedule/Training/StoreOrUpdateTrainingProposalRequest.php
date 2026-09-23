<?php

namespace App\Http\Requests\V1\Student\Schedule\Training;

use App\Enums\V2\Monitor\Reservation\Training\Proposal\ProposalStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrUpdateTrainingProposalRequest extends FormRequest
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
            'status' => ['nullable', new Enum(ProposalStatusEnum::class)],
            'offer_id' => 'required_if:status,==,3',
        ];
    }

    public function messages()
    {
        return [
            'status.enum' => 'Le statut sélectionné est invalide',
            'offer_id.required_if' => 'Un produit est requis pour reserver la séance',
            'offer_id.exists' => 'Le produit sélectionné est introuvable',
        ];
    }
}
