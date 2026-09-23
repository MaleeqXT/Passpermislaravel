<?php

namespace App\Http\Requests\V1\Monitor\Competency;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrCreateCompetencyRatingRequest extends FormRequest
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
            'comment' => 'nullable|string',
            'rating' => 'required | integer | between:1,3',
            'student_id' => 'required | exists:students,id',


        ];
    }

    public function messages()
    {
        return [
            'comment.required' => 'Le commentaire est obligatoire.',
            'rating.required' => 'La note est obligatoire.',
            'rating.integer' => 'La note doit être un nombre entier.',
            'rating.between' => 'La note doit être entre 1 et 3.',
            'student_id.required' => 'L\'élève est obligatoire.',
            'student_id.exists' => 'L\'élève n\'existe pas.',
        ];
    }
}
