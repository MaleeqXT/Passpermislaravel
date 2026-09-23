<?php

namespace App\Http\Requests\V1\Monitor\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrUpdateAccountMonitorRequest extends FormRequest
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
            'iban' => 'required|string|max:255',
            'bic' => 'required|string|max:255',
            'status' => ['nullable', new Enum(SituationStatusEnum::class)],
        ];
    }

    public function messages()
    {
        return [
            'iban.required' => 'Le champ "IBAN" est obligatoire.',
            'iban.string' => 'Le champ "IBAN" doit être une chaîne de caractères.',
            'iban.max' => 'Le champ "IBAN" ne doit pas dépasser 255 caractères.',
            'bic.required' => 'Le champ "BIC" est obligatoire.',
            'bic.string' => 'Le champ "BIC" doit être une chaîne de caractères.',
            'bic.max' => 'Le champ "BIC" ne doit pas dépasser 255 caractères.',
            'status.exists' => 'Le champ "statut" est invalide.',
            'status.enum' => 'Le champ "statut" doit être une valeur valide.',
            'status.required' => 'Le champ "statut" est obligatoire.',
            'status.string' => 'Le champ "statut" doit être une chaîne de caractères.',
            'status.max' => 'Le champ "statut" ne doit pas dépasser 255 caractères.',
        ];
    }
}
