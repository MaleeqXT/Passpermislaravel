<?php

namespace App\Http\Requests\V1\Admin\Params\Zone\Lieux;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLieuxRequest extends FormRequest
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
            'status' => 'required|boolean',
            'name' => 'required|string',
            'url' => 'nullable|string'
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Le status est obligatoire',
            'status.boolean' => 'Le status doit être un booléen',
            'name.required' => 'Le nom est obligatoire',
            'name.string' => 'Le nom doit être une chaine de caractères',
            'url.string' => 'L\'url doit être une chaine de caractères'

        ];
    }
}
