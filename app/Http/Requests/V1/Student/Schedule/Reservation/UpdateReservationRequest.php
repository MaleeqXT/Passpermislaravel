<?php

namespace App\Http\Requests\V1\Student\Schedule\Reservation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
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
            'reservation_id' => 'nullable|exists:reservations,id',
            'monitor_id' => 'required|exists:monitors,id',
            'date' => 'required_if:reservation_id,!=,null|date',
            'start_at' => 'required|date_format:H:i|before:end_at',
            'end_at' => 'required|date_format:H:i|after:start_at',
            'hour' => 'required',
            'lieu_id' => 'required|exists:lieux,id',
            'color' => 'nullable|string',
            'session_type' => 'nullable|string|max:100',
            'prestation' => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'offer_id.required' => 'Le produit est requis.',
            'offer_id.exists' => 'Le produit sélectionné est invalide.',
            'student_id.required' => 'L\'élève est requis.',
            'student_id.exists' => 'L\'élève sélectionné est invalide.',
            'reservation_id.required' => 'La Réservation est requis.',
            'reservation_id.exists' => 'La Réservation sélectionné est invalide.',
            'monitor_id.required' => 'Le Moniteur est requis.',
            'monitor_id.exists' => 'Le Moniteur sélectionné est invalide.',
            'date.required_if' => 'La date est requise lorsque la reservation est sélectionné.',
            'date.date' => 'La date doit être valide.',
            'date.after_or_equal' => 'La date doit être égale ou postérieure à la date actuelle.',
            'start_at.required' => 'L\'heure de début est requise.',
            'start_at.date_format' => 'L\'heure de début doit être au format HH:MM.',
            'start_at.before' => 'L\'heure de début doit être avant l\'heure de fin.',
            'end_at.required' => 'L\'heure de fin est requise.',
            'end_at.date_format' => 'L\'heure de fin doit être au format HH:MM.',
            'end_at.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'hour.required' => 'L\'heure est requise.',
            'lieu_id.required' => 'Le lieu est requis.',
            'lieu_id.exists' => 'Le lieu sélectionné est invalide.',
            'color.string' => 'La couleur doit être une chaîne de caractères.',
        ];
    }
    public function attributes()
    {
        return [
            'offer_id' => 'Offre',
            'student_id' => 'élève',
            'reservation_id' => 'reservation',
            'monitor_id' => 'Moniteur',
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
