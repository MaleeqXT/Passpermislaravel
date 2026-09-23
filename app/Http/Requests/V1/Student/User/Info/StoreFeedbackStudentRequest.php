<?php

namespace App\Http\Requests\V1\Student\User\Info;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackStudentRequest extends FormRequest
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
            'comment' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'student_id.required' => 'L\'élève est requis.',
            'student_id.exists' => 'L\'élève sélectionné n\'existe pas.',
            'comment.required' => 'Le commentaire est requis.',
            'comment.string' => 'Le commentaire doit être une chaîne de caractères.',
        ];
    }
}
