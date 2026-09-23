<?php

namespace App\Http\Requests\V1\Secretary;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Enums\V2\Admin\User\UserRolesEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class StoreSecretaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        // when updating, ignore the current user's email in unique rule
        $secretary = $this->route('secretary');
        $ignoreUserId = $secretary && isset($secretary->user) ? $secretary->user->id : null;
   
        return [
    'first_name'     => 'required|string|max:255',
    'last_name'      => 'required|string|max:255',
    'status'         => ['nullable', new Enum(SituationStatusEnum::class)],
    'adresse'        => 'required|string|max:255',
    'phone'          => 'required|string|max:255',
    'sexe'           => 'required|string|max:255',
    'date_naissance' => 'required|string|max:255',
    'postal'         => 'nullable|string|max:255',
    'neph'           => 'nullable|string|max:255',        // ✅ add karo
    'date_of_code'   => 'nullable|string|max:255',        // ✅ add karo
    'media'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',  // ✅ add karo

    'email' => $isCreate
        ? 'required|email|unique:users,email'
        : ['required', 'email', Rule::unique('users', 'email')->ignore($ignoreUserId)],

    'password' => $isCreate 
        ? 'required|string|min:8|confirmed' 
        : 'nullable|string|min:8|confirmed',
    //         'first_name' => 'required|string|max:255',
    //         'last_name' => 'required|string|max:255',
    //         'status' => [new Enum(SituationStatusEnum::class)],
    //         'adresse' => 'required|string|max:255',
    //         'phone' => 'required|string|max:255',
    //         // 'media' => 'nullable',

    //     // Email rule: unique on create, ignore current user on update
    // 'email' => $isCreate
    //     ? 'required|email|unique:users,email'
    //     : ['required', 'email', Rule::unique('users', 'email')->ignore($ignoreUserId)],

    // // Password: required on create, optional on update. Must be confirmed.
    // 'password' => $isCreate ? 'required|string|min:8|confirmed' : 'nullable|string|min:8|confirmed',


    //         'sexe' => 'required|string|max:255',
    //         'date_naissance' => 'required|string|max:255',
    //         'postal' => 'string|max:255',
    //         // 'ville' => 'required|string|max:255',

    //         // 'role'=>['nullable', default('secretary')],
    //         // 'role' => ['nullable', new Enum(UserRolesEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est requis.',
            'last_name.required' => 'Le nom est requis.',
            'status.required' => 'Le statut est requis.',
        ];
    }
}
