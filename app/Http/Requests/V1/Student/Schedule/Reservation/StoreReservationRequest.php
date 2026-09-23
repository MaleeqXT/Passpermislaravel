<?php

namespace App\Http\Requests\V1\Student\Schedule\Reservation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
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
            'student_id' => 'required|exists:students,id',
            'lieu_id' => 'required|exists:lieux,id',
            'monitor_id' => 'required|exists:monitors,id',
            'date' => 'required|date|after_or_equal:today',
            'start_at' => 'required|date_format:H:i|before:end_at',
            'end_at' => 'required|date_format:H:i|after:start_at',
            'color' => 'nullable|string',
            'session_type' => 'nullable|string|max:100',
            'prestation' => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'offer_id.required' => 'Le produit est nécessaire.',
            'offer_id.exists' => 'Le produit sélectionné est invalide.',
            'student_id.required' => 'L\'élève est nécessaire.',
            'student_id.exists' => 'L\'élève sélectionné est invalide.',
            'lieu_id.required' => 'Le lieu est nécessaire.',
            'lieu_id.exists' => 'Le lieu sélectionné est invalide.',
            'monitor_id.required' => 'Le Moniteur est nécessaire.',
            'monitor_id.exists' => 'Le Moniteur sélectionné est invalide.',
            'date.required' => 'La date est nécessaire.',
            'date.date' => 'La date doit être valide.',
            'date.after_or_equal' => 'La date doit être égale ou après aujourd\'hui.',
            'start_at.required' => 'L\'heure de début est nécessaire.',
            'start_at.date_format' => 'L\'heure de début doit être au format HH:MM.',
            'start_at.before' => 'L\'heure de début doit être avant l\'heure de fin.',
            'end_at.required' => 'L\'heure de fin est nécessaire.',
            'end_at.date_format' => 'L\'heure de fin doit être au format HH:MM.',
            'end_at.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'color.string' => 'La couleur doit être une chaîne de caractères.',
        ];
    }
    public function attributes()
    {
        return [
            'offer_id' => 'produit',
            'student_id' => 'élève',
            'reservation_id' => 'reservation',
            'monitor_id' => 'monitor',
            'date' => 'date',
            'start_at' => 'heure de début',
            'end_at' => 'heure de fin',
            'hour' => 'heure',
            'lieu_id' => 'lieu',
            'color' => 'couleur',
            'session_type' => 'type',
            'prestation' => 'prestation',
        ];
    }
}
