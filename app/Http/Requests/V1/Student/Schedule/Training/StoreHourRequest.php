<?php

namespace App\Http\Requests\V1\Student\Schedule\Training;

use Illuminate\Foundation\Http\FormRequest;

class StoreHourRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            // hours can be positive or negative, but cannot be zero
            'hours_requested' => ['required','integer','not_in:0'],
            'comment' => 'required|string',
            'reservation_id' => 'required|exists:reservations,id',
            'student_id' => 'nullable|uuid|exists:students,id',
        ];
    }

    public function messages(): array
    {
        return [
            'hours_requested.required' => 'Veuillez indiquer le nombre d\'heures (positif ou négatif).',
            'hours_requested.integer' => 'Le nombre d\'heures doit être un entier.',
            'hours_requested.not_in' => 'Le nombre d\'heures ne peut pas être zéro.',
            'comment.required' => 'Le commentaire est obligatoire.',
            'comment.string' => 'Le commentaire doit être une chaîne de caractères.',
        ];
    }
}
