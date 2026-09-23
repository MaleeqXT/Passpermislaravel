<?php

namespace  app\Http\Requests\V1\Admin\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UserRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email,' . $this->route('user'),
            'status' => [new Enum(SituationStatusEnum::class)],
            'adresse' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'media' => ['nullable', 'string'],
            'sexe' => 'nullable|string|max:255',
            'date_naissance' => 'nullable|string|max:255',
            'postal' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'password_confirmation' => 'nullable|string|min:8',
            'departement' => 'nullable|string',
            'numero_autorisation' => 'nullable|string',
            'tarif_enseignement' => 'nullable|string',
            'tarif_car' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'last_name.string' => 'Le nom doit être une chaîne de caractères.',
            'first_name.string' => 'Le prénom doit être une chaîne de caractères.',
            'last_name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'first_name.max' => 'Le prénom ne doit pas dépasser 255 caractères.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'status.required' => 'Le statut est obligatoire.',
            'status.enum' => 'Le statut doit être un des statuts autorisés.',
            'adresse.required' => 'L\'adresse est obligatoire.',
            'adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'adresse.max' => 'L\'adresse ne doit pas dépasser 255 caractères.',
            'phone.required' => 'Le téléphone est obligatoire.',
            'phone.string' => 'Le téléphone doit être une chaîne de caractères.',
            'phone.max' => 'Le téléphone ne doit pas dépasser 255 caractères.',
            'media.image' => 'La photo de profil doit être une image.',
            'media.mimes' => 'La photo de profil doit être de type jpeg, png, jpg, gif, ou svg.',
            'media.max' => 'La photo de profil ne doit pas dépasser 2 Mo.',
            'sexe.required' => 'Le sexe est obligatoire.',
            'sexe.string' => 'Le sexe doit être une chaîne de caractères.',
            'sexe.max' => 'Le sexe ne doit pas dépasser 255 caractères.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.string' => 'La date de naissance doit être une chaîne de caractères.',
            'date_naissance.max' => 'La date de naissance ne doit pas dépasser 255 caractères.',
            'postal.required' => 'Le code postal est obligatoire.',
            'postal.string' => 'Le code postal doit être une chaîne de caractères.',
            'postal.max' => 'Le code postal ne doit pas dépasser 255 caractères.',
            'ville.required' => 'La ville est obligatoire.',
            'ville.string' => 'La ville doit être une chaîne de caractères.',
            'ville.max' => 'La ville ne doit pas dépasser 255 caractères.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password_confirmation.required' => 'La confirmation du mot de passe est obligatoire.',
            'password_confirmation.string' => 'La confirmation du mot de passe doit être une chaîne de caractères.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.enum' => 'Le rôle doit être un des rôles autorisés.',
            'departement.required' => 'Le département est obligatoire.',
            'departement.string' => 'Le département doit être une chaîne de caractères.',
            'numero_autorisation.required' => 'Le numéro d\'autorisation est obligatoire.',
            'numero_autorisation.string' => 'Le numéro d\'autorisation doit être une chaîne de caractères.',
            'tarif_enseignement.required' => 'Le tarif d\'enseignement est obligatoire.',
            'tarif_enseignement.string' => 'Le tarif d\'enseignement doit être une chaîne de caractères.',
            'tarif_car.required' => 'Le tarif de la voiture est obligatoire.',
            'tarif_car.string' => 'Le tarif de la voiture doit être une chaîne de caractères.',
        ];
    }
}
