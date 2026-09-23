<?php

namespace App\Http\Requests\V1\Student\Schedule;

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
            'view' => 'nullable|string|in:week,month',
            'start' => 'nullable|date',
            'end' => 'nullable|date',
            'monitor_id' => 'nullable|exists:monitors,id',
            'lieu_id' => 'nullable|exists:lieux,id',
            'zone_id' => 'nullable|exists:zones,id',
            'student_id' => 'nullable|exists:students,id',
            'is_disponible' => 'nullable|boolean',
            'disp' => 'nullable',
            'color' => 'nullable',
            'session_type' => 'nullable|string|max:100',
            'prestation' => 'nullable|string|max:100',

        ];
    }

    public function messages()
    {
        return [
            'view.required' => 'Veuillez spécifier la vue.',
            'view.string' => 'La vue doit être une chaîne de caractères.',
            'view.in' => 'La vue doit être soit "semaine" soit "mois".',
            'start.date' => 'La date de début doit être valide.',
            'end.date' => 'La date de fin doit être valide.',
            'monitor_id.string' => 'L\'identifiant du moniteur doit être une chaîne de caractères.',
            'monitor_id.exists' => 'Le Moniteur sélectionné est introuvable.',
            'student_id.string' => 'L\'identifiant de l\'élève doit être une chaîne de caractères.',
            'student_id.exists' => 'L\'élève sélectionné est introuvable.',
            'is_disponible.boolean' => 'Le champ de disponibilité doit être un booléen.',
        ];
    }
}
