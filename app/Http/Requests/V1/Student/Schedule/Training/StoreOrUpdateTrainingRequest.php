<?php

namespace App\Http\Requests\V1\Student\Schedule\Training;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateTrainingRequest extends FormRequest
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
            'offer_id' => 'required|exists:offers,id',
            'date' => 'required|date|after_or_equal:today',
            'start_at' => 'required|date_format:H:i|before:end_at',
            'end_at' => 'required|date_format:H:i|after:start_at',
            'hour' => 'nullable|integer|min:1',
            'lieu_id' => 'nullable|exists:lieux,id',
        ];
    }

    public function messages()
    {
        return [
            'offer_id.required' => 'Veuillez sélectionner un produit',
            'offer_id.exists' => 'Le produit sélectionné est introuvable',
            'reservation_id.required' => 'La resrvation est requis',
            'reservation_id.exists' => 'La resrvation sélectionné est introuvable',
            'student_id.required' => 'Veuillez sélectionner un élève',
            'student_id.exists' => 'L\'élève sélectionné est introuvable',
            'monitor_id.required' => 'Veuillez sélectionner un Moniteur',
            'monitor_id.exists' => 'Le Moniteur sélectionné est introuvable',
            'date.required_if' => 'La date est obligatoire dans ce cas',
            'date.date' => 'Veuillez entrer une date valide',
            'date.after_or_equal' => 'La date doit être égale ou postérieure à aujourd\'hui',
            'start_at.required_if' => 'L\'heure de début est obligatoire dans ce cas',
            'start_at.date_format' => 'L\'heure de début doit respecter un format valide',
            'start_at.before' => 'L\'heure de début doit être avant l\'heure de fin',
            'end_at.required_if' => 'L\'heure de fin est obligatoire dans ce cas',
            'end_at.date_format' => 'L\'heure de fin doit respecter un format valide',
            'end_at.after' => 'L\'heure de fin doit être après l\'heure de début',
        ];
    }
}
