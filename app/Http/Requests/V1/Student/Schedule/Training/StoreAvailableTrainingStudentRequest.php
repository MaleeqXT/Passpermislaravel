<?php

namespace App\Http\Requests\V1\Student\Schedule\Training;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAvailableTrainingStudentRequest extends FormRequest
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
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'time_at' => 'required|date_format:H:i',
        ];
    }

    public function messages()
    {
        return [
            'student_id.required' => 'Le champ "élève" est obligatoire.',
            'student_id.exists' => 'L\'élève sélectionné n\'existe pas.',
            'date.required' => 'Le champ "date" est obligatoire.',
            'date.date' => 'La date doit être valide.',
            'time_at.required' => 'Le champ "heure" est obligatoire.',
            'time_at.date_format' => 'L\'heure doit être au format H:i.',
        ];
    }
}
