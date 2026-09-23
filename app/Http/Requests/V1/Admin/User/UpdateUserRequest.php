<?php

namespace  app\Http\Requests\V1\Admin\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Enums\V2\Admin\User\UserRolesEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'status' => [new Enum(SituationStatusEnum::class)],
            'phone' => 'nullable|string|max:255',
            'media' => ['nullable', 'string', 'max:1024'],
            'role' => ['required', new Enum(UserRolesEnum::class)],
            'password' => 'nullable|string|min:4|max:255',
            'password_confirmation' => 'nullable|string|min:4|max:255|same:password',
        ];
    }

    public function messages()
    {
        return [
            'first_name.required' => 'Le prenom est obligatoire',
            'last_name.required' => 'Le nom est obligatoire',
            'last_name.string' => 'Le nom doit être une chaîne de caractères',
            'first_name.string' => 'Le prenom doit être une chaîne de caractères',
            'last_name.max' => 'Le nom ne doit pas dépasser 255 caractères',
            'first_name.max' => 'Le prenom ne doit pas dépasser 255 caractères',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être une adresse email valide',
            'email.unique' => 'L\'email doit être unique',
            'status.required' => 'Le status est obligatoire',
            'status.enum' => 'Le status doit être un des status autorisés',
            'adresse.required' => 'L\'adresse est obligatoire',
            'adresse.string' => 'L\'adresse doit être une chaîne de caractères',
            'adresse.max' => 'L\'adresse ne doit pas dépasser 255 caractères',
            'phone.required' => 'Le téléphone est obligatoire',
            'phone.string' => 'Le téléphone doit être une chaîne de caractères',
            'phone.max' => 'Le téléphone ne doit pas dépasser 255 caractères',
            'media.image' => 'La photo de profil doit être une image',
            'media.mimes' => 'La photo de profil doit être une image de type jpeg, png, jpg, gif, svg',
            'media.max' => 'La photo de profil ne doit pas dépasser 2048 caractères',
            'sexe.required' => 'Le sexe est obligatoire',
            'sexe.string' => 'Le sexe doit être une chaîne de caractères',
            'sexe.max' => 'Le sexe ne doit pas dépasser 255 caractères',
            'date_naissance.required' => 'La date de naissance est obligatoire',
            'date_naissance.string' => 'La date de naissance doit être une chaîne de caractères',
            'date_naissance.max' => 'La date de naissance ne doit pas dépasser 255 caractères',
            'postal.required' => 'Le code postal est obligatoire',
            'postal.string' => 'Le code postal doit être une chaîne de caractères',
            'postal.max' => 'Le code postal ne doit pas dépasser 255 caractères',
            'ville.required' => 'La ville est obligatoire',
            'ville.string' => 'La ville doit être une chaîne de caractères',
            'ville.max' => 'La ville ne doit pas dépasser 255 caractères',
            'role.required' => 'Le role est obligatoire',
            'role.enum' => 'Le role doit être un des roles autorisés',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.string' => 'Le mot de passe doit être une chaîne de caractères',
            'password.min' => 'Le mot de passe doit être au moins de 4 caractères',
            'password.max' => 'Le mot de passe ne doit pas dépasser 255 caractères',
            'password_confirmation.required' => 'La confirmation du mot de passe est obligatoire',
            'password_confirmation.string' => 'La confirmation du mot de passe doit être une chaîne de caractères',
            'password_confirmation.min' => 'La confirmation du mot de passe doit être au moins de 4 caractères',
            'password_confirmation.max' => 'La confirmation du mot de passe ne doit pas dépasser 255 caractères',
            'password_confirmation.same' => 'La confirmation du mot de passe doit être identique au mot de passe',

        ];
    }
}
