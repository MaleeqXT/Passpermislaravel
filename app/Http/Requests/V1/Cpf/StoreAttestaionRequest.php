<?php

namespace App\Http\Requests\V1\Cpf;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttestaionRequest extends FormRequest
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
            'q8' => 'required|string',
            'q9' => 'required|array',

        ];
    }

    public function messages()
    {
        return [
            'q8.required' => 'Le champ "domicilié(e) à" est obligatoire.',
            'q9.required' => 'il faut accepté les conditions ',


        ];
    }
}
