<?php

namespace App\Http\Requests\V1\Student\Competency;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateCompetencyRequest extends FormRequest
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
            'label' => 'required|string|max:255',
            'status' => 'boolean',
            'main_competency_id' => 'required|exists:main_competencies,id',
            'position' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'label.required' => 'Le "label" est requis.',
            'status.required' => 'Le "statut" est requis.',
            'position.required' => 'La "position" est requise.',
            'main_competency_id.required' => 'Le "groupe de compétence" est requis.',
            'main_competency_id.exists' => 'Le groupe de compétence sélectionné est introuvable.',
            'main_competency_id.integer' => 'Le groupe de compétence doit être un nombre entier.',
            'position.integer' => 'La position doit être un nombre entier.',
            'label.string' => 'Le "label" doit être une chaîne de caractères.',
            'label.max' => 'Le "label" ne doit pas dépasser 255 caractères.',
            'status.string' => 'Le "statut" doit être une chaîne de caractères.',
            'status.max' => 'Le "statut" ne doit pas dépasser 255 caractères.',
            'position.string' => 'La "position" doit être une chaîne de caractères.',
            'position.max' => 'La "position" ne doit pas dépasser 255 caractères.',
            'main_competency_id.string' => 'Le groupe de compétence doit être une chaîne de caractères.',
            'main_competency_id.max' => 'Le groupe de compétence ne doit pas dépasser 255 caractères.',
            'main_competency_id.unique' => 'Le groupe de compétence doit être unique.',
        ];
    }
}
