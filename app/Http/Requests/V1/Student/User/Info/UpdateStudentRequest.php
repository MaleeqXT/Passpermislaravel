<?php

namespace App\Http\Requests\V1\Student\User\Info;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Enums\V2\Student\User\StudentVitalBoxTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;
class UpdateStudentRequest extends FormRequest
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
            'neph' => 'nullable|numeric',
            'is_cpf' => 'required|boolean',
            'date_code' => 'nullable|date',
            'date_expiration_code' => 'nullable|date',
            'date_expiration_formula' => 'nullable|date',
            'balance' => 'nullable|numeric|min:0',
            'estimation' => 'nullable|numeric|min:0',
            'memo' => 'nullable|string|max:10000',
            'memo_color' => 'nullable|string|max:20',
            'how_know' => 'nullable|string',
            'boite_type' => [new Enum(StudentVitalBoxTypeEnum::class)],
            // 'frequence' => 'nullable|integer',
            // 'estimation' => 'nullable|integer',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->route('student')->user_id,
            // 'email' => [
            //     'required',
            //     'email',
            //     Rule::unique('users', 'email')->ignore($this->route('student')->user_id),
            // ],
            'status' => [new Enum(SituationStatusEnum::class)],
            'adresse' => 'nullable|string|max:255',
            'phone' => 'required|string|max:255',
       'media' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:6024',
            'sexe' => 'nullable|string|max:255',
            'date_naissance' => 'required|string|max:255',
            'postal' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            // 'role' => ['required', new Enum(UserRolesEnum::class)],
            'password' => 'nullable|string|min:4|max:255',
            'password_confirmation' => 'nullable|string|min:4|max:255|same:password',

            'contract_path' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'neph.required' => 'Le NEPH est requis.',
            'neph.string' => 'Le NEPH doit être une chaîne de caractères.',
            'neph.unique' => 'Le NEPH est déjà utilisé.',
            'is_cpf.required' => 'Le CPF est requis.',
            'is_cpf.boolean' => 'Le CPF doit être un booléen.',
            'date_code.required' => 'La date du code est requise.',
            'date_code.date' => 'La date du code doit être valide.',
            'how_know.required' => 'La source est requise.',
            'how_know.string' => 'La source doit être une chaîne de caractères.',

            'first_name.required' => 'Le prénom est requis.',
            'last_name.required' => 'Le nom est requis.',
            'last_name.string' => 'Le nom doit être une chaîne de caractères.',
            'first_name.string' => 'Le prénom doit être une chaîne de caractères.',
            'last_name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'first_name.max' => 'Le prénom ne doit pas dépasser 255 caractères.',
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être une adresse valide.',
            'status.required' => 'Le statut est requis.',
            'status.enum' => 'Le statut doit être l\'un des statuts autorisés.',
            'adresse.required' => 'L\'adresse est requise.',
            'adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'adresse.max' => 'L\'adresse ne doit pas dépasser 255 caractères.',
            'phone.required' => 'Le téléphone est requis.',
            'phone.string' => 'Le téléphone doit être une chaîne de caractères.',
            'phone.max' => 'Le téléphone ne doit pas dépasser 255 caractères.',
            'media.image' => 'La photo de profil doit être une image.',
            'media.mimes' => 'La photo de profil doit être au format jpeg, png, jpg, gif, ou svg.',
            'media.max' => 'La photo de profil ne doit pas dépasser 2 Mo.',
            'sexe.required' => 'Le sexe est requis.',
            'sexe.string' => 'Le sexe doit être une chaîne de caractères.',
            'sexe.max' => 'Le sexe ne doit pas dépasser 255 caractères.',
            'date_naissance.required' => 'La date de naissance est requise.',
            'date_naissance.string' => 'La date de naissance doit être une chaîne de caractères.',
            'date_naissance.max' => 'La date de naissance ne doit pas dépasser 255 caractères.',
            'postal.required' => 'Le code postal est requis.',
            'postal.string' => 'Le code postal doit être une chaîne de caractères.',
            'postal.max' => 'Le code postal ne doit pas dépasser 255 caractères.',
            'ville.required' => 'La ville est requise.',
            'ville.string' => 'La ville doit être une chaîne de caractères.',
            'ville.max' => 'La ville ne doit pas dépasser 255 caractères.',

            'password.required' => 'Le mot de passe est requis.',
            'password.string' => 'Le mot de passe doit être une chaîne de caractères.',
            'password.min' => 'Le mot de passe doit comporter au moins 4 caractères.',
            'password.max' => 'Le mot de passe ne doit pas dépasser 255 caractères.',
            'password_confirmation.required' => 'La confirmation du mot de passe est requise.',
            'password_confirmation.string' => 'La confirmation du mot de passe doit être une chaîne de caractères.',
            'password_confirmation.min' => 'La confirmation du mot de passe doit comporter au moins 4 caractères.',
            'password_confirmation.max' => 'La confirmation du mot de passe ne doit pas dépasser 255 caractères.',
            'password_confirmation.same' => 'La confirmation du mot de passe doit être identique au mot de passe.',
        ];
    }
}
