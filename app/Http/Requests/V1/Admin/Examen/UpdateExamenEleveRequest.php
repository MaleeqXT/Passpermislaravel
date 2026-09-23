<?php

namespace App\Http\Requests\V1\Admin\Examen;

use App\Enums\V2\Student\Examen\ExamenResultPermisEnum;
use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateExamenEleveRequest extends FormRequest
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
            'student_id' => 'nullable|exists:students,id',
            'user_id' => 'nullable|exists:users,id',
            'comment' => 'nullable|string',
            'date_examen' => 'nullable|date',
            'heure_passage' => 'nullable|string',
            'result_permis' => ['nullable', new Enum(ExamenResultPermisEnum::class)],
            'status' => ['nullable', new Enum(ExamenStatusEnum::class)],

        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Veuillez fournir l\'identifiant de l\'élève.',
            'student_id.exists' => 'L\'identifiant de l\'élève est invalide.',
            'user_id.required' => 'Veuillez fournir l\'identifiant de l\'utilisateur.',
            'user_id.exists' => 'L\'identifiant de l\'utilisateur est invalide.',
            'type.required' => 'Veuillez indiquer le type.',
            'type.string' => 'Le type doit être au format texte.',
            'date_pass_prevu.required' => 'Veuillez fournir la date de passage prévue.',
            'date_pass_prevu.string' => 'La date de passage prévue doit être au format texte.',
            'comment.required' => 'Veuillez fournir un commentaire.',
            'comment.string' => 'Le commentaire doit être au format texte.',
        ];
    }
}
