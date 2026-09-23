<?php

namespace App\Http\Requests\V1\Admin\Page;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use function App\Http\Requests\Admin\Livre\str_contains;

class StoreorUpdatePromoPerUserRequest extends FormRequest
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
            'title' => 'nullable|string',
            'user_id' => 'required| exists:users,id',
            'is_active' => 'required|boolean',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
            'extra' => 'nullable|array',
        ];
    }


    public function messages()
    {
        return [
            'user_id.required' => 'Veuillez fournir l\'identifiant de l\'utilisateur.',
            'user_id.exists' => 'L\'utilisateur sélectionné n\'existe pas.',
            'is_active.required' => 'Veuillez indiquer si l\'utilisateur est actif.',
            'is_active.boolean' => 'Le champ "Actif" doit être un booléen.',
            'start_at.required' => 'Veuillez fournir la date de début.',
            'start_at.date' => 'La date de début doit être au format valide.',
            'end_at.required' => 'Veuillez fournir la date de fin.',
            'end_at.date' => 'La date de fin doit être au format valide.',
            'extra.json' => 'Le champ "Extra" doit contenir un format JSON valide.',
        ];
    }
}
