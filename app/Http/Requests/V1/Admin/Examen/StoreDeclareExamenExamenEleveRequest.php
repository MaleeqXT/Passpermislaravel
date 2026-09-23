<?php

namespace App\Http\Requests\V1\Admin\Examen;

use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDeclareExamenExamenEleveRequest extends FormRequest
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
            'monitor_id' => 'nullable|exists:monitors,id',
            'lieu_id' => 'required|exists:lieux,id',
            'date_examen' => 'required|string',
            'date_passage' => 'required|string',
            'heure_passage' => 'required|string',
            'status' => ['required', new Enum(ExamenStatusEnum::class)],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'monitor_id.exists' => 'Le Moniteur sélectionné n\'existe pas.',
            'lieu_id.exists' => 'Le lieu sélectionné n\'existe pas.',
            'date_examen.string' => 'La date de l\'examen doit être au format texte.',
            'date_passage.required' => 'Veuillez fournir la date de passage.',
            'date_passage.string' => 'La date de passage doit être au format texte.',
            'heure_passage.required' => 'Veuillez fournir l\'heure de passage.',
            'heure_passage.string' => 'L\'heure de passage doit être au format texte.',
            'status.required' => 'Veuillez indiquer le statut.',
            'status.string' => 'Le statut doit être au format texte.',
        ];
    }
}
