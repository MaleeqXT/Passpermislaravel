<?php

namespace App\Http\Requests\V1\Student\Competency;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateMainCompetencyRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'status' => 'boolean',
            'position' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'label.required' => 'Le "label" est obligatoire.',
            'name.required' => 'Le "nom" est obligatoire.',
            'status.required' => 'Le "statut" est obligatoire.',
            'position.required' => 'La "position" est obligatoire.',
        ];
    }
}
