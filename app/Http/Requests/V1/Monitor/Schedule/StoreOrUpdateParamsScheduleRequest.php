<?php

namespace App\Http\Requests\V1\Monitor\Schedule;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrUpdateParamsScheduleRequest extends FormRequest
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
            'status' => ['required', new Enum(SituationStatusEnum::class)],
            'start_at' => 'required|date',
            'end_at' => 'required|date',
            'lieu_id' => 'required|exists:lieux,id',
            'days' => 'required|array',
            'days.*.*' => 'nullable|array',
            'days.*.*.*' => 'nullable|array',
            'days.*.*.*.start_at' => 'required|date_format:H:i',
            'days.*.*.*.end_at' => 'required|date_format:H:i|after:days.*.*.*.start_at',
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Le champ "statut" est obligatoire.',
            'status.enum' => 'Le champ "statut" doit être une valeur valide.',
            'start_at.required' => 'Le champ "heure de début" est obligatoire.',
            'start_at.date' => 'Le champ "heure de début" doit être une date valide.',
            'end_at.required' => 'Le champ "heure de fin" est obligatoire.',
            'end_at.date' => 'Le champ "heure de fin" doit être une date valide.',
            'lieu_id.required' => 'Le champ "lieu" est obligatoire.',
            'lieu_id.exists' => 'Le lieu spécifié est invalide.',
            'days.required' => 'Le champ "jours" est obligatoire.',
            'days.array' => 'Le champ "jours" doit être un tableau.',
            'days.*.array' => 'Le champ "jour" doit être un tableau.',
            'days.*.*.required' => 'Le champ "heure" est obligatoire.',
            'days.*.*.start_at.date_format' => 'L\'heure de début doit être au format valide (H:i).',
            'days.*.*.end_at.date_format' => 'L\'heure de fin doit être au format valide (H:i).',
            'days.*.*.end_at.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'days.*.*.start_at.required' => 'L\'heure de début est obligatoire.',
            'days.*.*.end_at.required' => 'L\'heure de fin est obligatoire.',
        ];
    }

    public function attributes()
    {
        return [
            'status' => 'statut',
            'lieu_id' => 'lieu',
            'days' => 'jours',
            'days.*' => 'jour',
            'days.*.*' => 'plage horaire',
            'days.*.*.start_at' => 'heure de début',
            'days.*.*.end_at' => 'heure de fin',
        ];
    }
}
