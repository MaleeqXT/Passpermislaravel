<?php

namespace App\Http\Requests\V1\Secretary;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Enums\V2\Admin\User\UserRolesEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateSecretaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

  public function rules(): array
{
    $userId = $this->route('secretary')->user_id;

    return [
        'first_name' => 'required|string|max:255',
        'last_name'  => 'required|string|max:255',
        'status'     => [new Enum(SituationStatusEnum::class)],

        'email' => [
            'nullable',
            'email',
            Rule::unique('users', 'email')->ignore($userId),
        ],

        'adresse' => 'required|string|max:255',
        'phone'   => 'required|string|max:255',
        'media'   => 'nullable',
        'sexe'    => 'required|string|max:255',
        'date_naissance' => 'required|string|max:255',
        'postal'  => 'required|string|max:255',
        'ville'   => 'required|string|max:255',
        'role'    => ['nullable', new Enum(UserRolesEnum::class)],

        // ✅ Add this for password update (optional)
        'password' => 'nullable|string|min:6|confirmed',
        'password_confirmation' => 'nullable|string|min:6',
    ];
}

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est requis.',
            'email.email'    => 'L\'email doit être une adresse valide.',
            'first_name.required' => 'Le prénom est requis.',
            'last_name.required'  => 'Le nom est requis.',
            'status.required'     => 'Le statut est requis.',
        ];
    }
}
