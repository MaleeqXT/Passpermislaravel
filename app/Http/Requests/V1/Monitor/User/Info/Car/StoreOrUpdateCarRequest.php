<?php

namespace App\Http\Requests\V1\Monitor\User\Info\Car;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateCarRequest extends FormRequest
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
            'media_carte' => 'nullable|array',
            'media_assurance' => 'nullable|array',
            'media_carte.*' => 'nullable|string|exists:storage_media,id',
            'media_assurance.*' => 'nullable|string|exists:storage_media,id',
            'marque' => 'required|string',
            'modele' => 'required|string',
            'immatriculation' => 'required|string',
            'date_achat' => 'required|date',
            'date_control_tech' => 'required|date',
            'date_assurance' => 'required|date',
            'color' => 'required|string',
            'is_auto' => 'required|boolean',

        ];
    }

    public function messages()
    {
        return [
            'media_carte.required' => 'Le champ "média carte" est obligatoire.',
            'media_carte.*.exists' => 'Ce média est invalide.',
            'media_assurance.required' => 'Le champ "média assurance" est obligatoire.',
            'media_assurance.*.exists' => 'Ce média est invalide.',
            'marque.required' => 'Le champ "marque" est obligatoire.',
            'marque.string' => 'Le champ "marque" doit être une chaîne de caractères.',
            'modele.required' => 'Le champ "modèle" est obligatoire.',
            'modele.string' => 'Le champ "modèle" doit être une chaîne de caractères.',
            'immatriculation.required' => 'Le champ "immatriculation" est obligatoire.',
            'immatriculation.string' => 'Le champ "immatriculation" doit être une chaîne de caractères.',
            'date_achat.required' => 'Le champ "date d\'achat" est obligatoire.',
            'date_achat.date' => 'Le champ "date d\'achat" doit être une date valide.',
            'date_control_tech.required' => 'Le champ "date de contrôle technique" est obligatoire.',
            'date_control_tech.date' => 'Le champ "date de contrôle technique" doit être une date valide.',
            'date_assurance.required' => 'Le champ "date d\'assurance" est obligatoire.',
            'date_assurance.date' => 'Le champ "date d\'assurance" doit être une date valide.',
            'color.required' => 'Le champ "couleur" est obligatoire.',
            'color.string' => 'Le champ "couleur" doit être une chaîne de caractères.',
            'is_auto.required' => 'Le champ "automatique" est obligatoire.',
            'is_auto.boolean' => 'Le champ "automatique" doit être un booléen.',
        ];
    }
}
