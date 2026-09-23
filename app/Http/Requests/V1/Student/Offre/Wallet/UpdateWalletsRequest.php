<?php

namespace App\Http\Requests\V1\Student\Offre\Wallet;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletsRequest extends FormRequest
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
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Le statut est requis.',
            'status.boolean' => 'Le statut doit être un valeur booléenne.',
        ];
    }
}
