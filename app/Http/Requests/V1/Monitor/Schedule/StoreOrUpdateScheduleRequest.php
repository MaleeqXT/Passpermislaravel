<?php

namespace App\Http\Requests\V1\Monitor\Schedule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateScheduleRequest extends FormRequest
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
            'date' => 'required|date|after_or_equal:today',
            'start_at' => 'required|date_format:H:i|before:end_at',
            'end_at' => 'required|date_format:H:i|after:start_at',
            'is_active' => 'nullable|boolean',
            'hour' => 'nullable|integer|min:1',
            'monitor_id' => 'nullable|exists:monitors,id',
            'lieu_id' => 'required|exists:lieux,id',

        ];
    }

    public function messages()
    {
        return [
            'date.required' => 'La date est obligatoire.',
            'date.date' => 'La date doit être valide.',
            'date.after_or_equal' => 'La date doit être égale ou postérieure à aujourd\'hui.',
            'start_at.required' => 'L\'heure de début est obligatoire.',
            'start_at.date_format' => 'L\'heure de début doit être un format horaire valide.',
            'start_at.before' => 'L\'heure de début doit être avant l\'heure de fin.',
            'end_at.required' => 'L\'heure de fin est obligatoire.',
            'end_at.date_format' => 'L\'heure de fin doit être un format horaire valide.',
            'end_at.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'is_active.boolean' => 'Le statut doit être un booléen.',
            'hour.integer' => 'Le nombre d\'heures doit être un entier.',
            'hour.min' => 'Le nombre d\'heures doit être supérieur à 0.',
            'monitor_id.exists' => 'Le Moniteur spécifié doit exister.',
        ];
    }
}
