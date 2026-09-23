<?php

namespace App\Http\Requests\V1\Monitor\Competency;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrCreateCompetencyStudentRequest extends FormRequest
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
            'student_id' => 'required | exists:students,id',
        ];
    }

    public function messages()
    {
        return [
            'student_id.required' => 'L\'identifiant de l\'élève est requis.',
            'student_id.exists' => 'L\'élève n\'existe pas dans la base de données.',
        ];
    }
}
