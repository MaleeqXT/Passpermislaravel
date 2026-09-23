<?php

namespace App\Http\Requests\V1\Student\User\Info;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateCallRequest extends FormRequest
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
            'student_id.required' => 'L\'élève est requis.',
            'student_id.exists' => 'L\'élève sélectionné n\'existe pas.',
            'date.required' => 'La date est requise.',
            'date.date' => 'La date doit être valide.',
            'time_at.required' => 'L\'heure est requise.',
            'time_at.date_format' => 'L\'heure doit être au format H:i.',
        ];
    }
}
