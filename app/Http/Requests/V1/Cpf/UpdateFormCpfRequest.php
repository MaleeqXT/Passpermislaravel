<?php

namespace App\Http\Requests\V1\Cpf;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFormCpfRequest extends FormRequest
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
            'boite' => 'required|array',
            'reservations' => 'required|array',
            'offre' => 'required|integer',
            'numero_cpf' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'boite.required' => 'Le champ boite est requis.',
            'boite.string' => 'Le champ boite doit être une chaîne de caractères.',
            'offre.required' => 'Le champ offre est requis.',
            'offre.integer' => 'Le champ offre doit valider.',
            'reservations.required' => 'Le champ reservations est requis.',
            'numero_cpf.required' => 'Le champ numero cpf est requis.',

        ];
    }
}
