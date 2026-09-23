<?php

namespace App\Http\Requests\V1\Student\Cpf\EleveCpf;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCpfEleveRequest extends FormRequest
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
            'student_id.required' => 'Le champ "student_id" est obligatoire.',
            'student_id.exists' => 'L\'élève sélectionné n\'existe pas.',
            'offer_id.required' => 'Le champ "offer_id" est obligatoire.',
            'offer_id.exists' => 'L\'offre sélectionnée n\'existe pas.',
            'start_at.required' => 'Le champ "start_at" est obligatoire.',
            'start_at.date' => 'Le champ "start_at" doit être une date valide.',
            'end_at.required' => 'Le champ "end_at" est obligatoire.',
            'end_at.date' => 'Le champ "end_at" doit être une date valide.',
        ];
    }
}
