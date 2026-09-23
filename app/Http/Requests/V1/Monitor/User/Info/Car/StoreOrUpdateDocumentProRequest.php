<?php

namespace App\Http\Requests\V1\Monitor\User\Info\Car;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateDocumentProRequest extends FormRequest
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
            'denomination_social' => 'nullable|string',
            'forme_juridique' => 'nullable|string',
            'siret' => 'nullable|string',
            'num_autorisation' => 'nullable|string',
            'date_creation' => 'nullable|date',
            'autorisations' => 'nullable',
            'autorisations.media' => 'nullable|array',
            'autorisations.media.*' => 'nullable|string',
            'autorisations.autorisation' => 'nullable|date',
            'autorisations.visite' => 'nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'denomination_social.string' => 'La dénomination sociale doit être valide.',
            'forme_juridique.string' => 'La forme juridique doit être valide.',
            'siret.string' => 'Le numéro SIRET doit être valide.',
            'num_autorisation.string' => 'Le numéro d\'autorisation doit être valide.',
            'date_creation.date' => 'La date de création doit être valide.',
            'autorisations.media' => 'Le fichier est obligatoire.',
            'autorisations.media.*' => 'Le fichier est obligatoire.',
            'autorisations.media.*.string' => 'Le fichier doit être sélectionné correctement.',
            'autorisations.autorisation.date' => 'La date d\'autorisation doit être valide.',
            'autorisations.visite.date' => 'La date de visite doit être valide.',
        ];
    }
}
