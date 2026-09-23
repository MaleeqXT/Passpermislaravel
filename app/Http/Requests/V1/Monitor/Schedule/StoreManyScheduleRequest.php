<?php

namespace App\Http\Requests\V1\Monitor\Schedule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreManyScheduleRequest extends FormRequest
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
            // The selected location can come from either the monitor or
            // administration location list; resolution happens in the controller.
            'lieu_id' => 'nullable|string',
            // The dashboard may receive a monitor UUID or its linked user UUID.
            'monitor_id' => 'nullable|uuid',
            'data' => 'required|array',
            // The monitor calendar can be viewed and managed for any week.
            'data.*.date' => 'required|date',
            'data.*.start_at' => 'required|date_format:H:i|before:end_at',
            'data.*.end_at' => 'required|date_format:H:i|after:start_at',
            'data.*.is_active' => 'nullable|boolean',
            'data.*.hour' => 'nullable|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'data.*.date.required' => 'Le champ "date" est obligatoire.',
            'data.*.date.date' => 'Le champ "date" doit être une date valide.',
            'data.*.start_at.required' => 'Le champ "heure de début" est obligatoire.',
            'data.*.start_at.date_format' => 'Le champ "heure de début" doit être au format horaire valide.',
            'data.*.start_at.before' => 'L\'heure de début doit être avant l\'heure de fin.',
            'data.*.end_at.required' => 'Le champ "heure de fin" est obligatoire.',
            'data.*.end_at.date_format' => 'Le champ "heure de fin" doit être au format horaire valide.',
            'data.*.end_at.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'data.*.is_active.boolean' => 'Le champ "actif" doit être un booléen.',
            'data.*.hour.integer' => 'Le champ "heure" doit être un entier.',
            'data.*.hour.min' => 'Le champ "heure" doit être un entier positif.',
            'lieu_id.required' => 'Le champ "lieu" est obligatoire.',
            'lieu_id.exists' => 'Le lieu spécifié est invalide.',
        ];
    }
}
