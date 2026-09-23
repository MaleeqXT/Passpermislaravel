<?php

namespace App\Http\Requests\V1\Student\User\Params;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNephRequest extends FormRequest
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
            'neph' => 'required|numeric',
            'date_code' => 'required|date',
        ];
    }

    public function messages()
    {
        return [
            'neph.required' => 'Le NEPH est obligatoire',
            'neph.string' => 'Le NEPH doit être une chaîne de caractères',
            'neph.unique' => 'Le NEPH est déjà utilisé',
            'date_code.required' => 'La date du code est obligatoire',
            'date_code.date' => 'La date du code doit être une date',
        ];
    }
}
