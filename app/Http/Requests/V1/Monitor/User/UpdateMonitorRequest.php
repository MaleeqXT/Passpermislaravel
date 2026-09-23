<?php

namespace App\Http\Requests\V1\Monitor\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateMonitorRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email,' . $this->route('monitor')->user_id,
            'status' => [new Enum(SituationStatusEnum::class)],
            'adresse' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
           'media'  => 'nullable|image|mimes:jpeg,png,jpg,webp',
            'sexe'=> 'required|string|max:255',
            'date_naissance' => 'required|string|max:255',
            'postal' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'is_manual' => 'nullable|boolean',
            'is_auto' => 'nullable|boolean',
            // 'role' => ['required', new Enum(UserRolesEnum::class)],
            'lieux.*' => 'nullable|exists:lieux,id',
            'password' => 'nullable|string|min:4|max:255',
            'password_confirmation' => 'nullable|string|min:4|max:255|same:password',

            'experience' => 'nullable|integer',
            'dernier_experience' => 'nullable|string',
            'details_experience' => 'nullable|string',

            'departement' => 'nullable|string',
            'numero_autorisation' => 'nullable|string',
            'tarif_enseignement' => 'nullable|string',
            'tarif_car' => 'nullable|string',

        ];
    }

    public function messages()
    {
        return [
            'experience.required' => 'Le champ "expérience" est obligatoire.',
            'dernier_experience.required' => 'Le champ "dernière expérience" est obligatoire.',
            'details_experience.required' => 'Le champ "détails de l\'expérience" est obligatoire.',
            'experience.integer' => 'Le champ "expérience" doit être un nombre.',
            'first_name.required' => 'Le champ "prénom" est obligatoire.',
            'last_name.required' => 'Le champ "nom" est obligatoire.',
            'last_name.string' => 'Le champ "nom" doit être une chaîne de caractères.',
            'first_name.string' => 'Le champ "prénom" doit être une chaîne de caractères.',
            'last_name.max' => 'Le champ "nom" ne doit pas dépasser 255 caractères.',
            'first_name.max' => 'Le champ "prénom" ne doit pas dépasser 255 caractères.',
            'email.required' => 'Le champ "email" est obligatoire.',
            'email.email' => 'Le champ "email" doit être une adresse email valide.',
            'email.unique' => 'L\'email doit être unique.',
            'status.required' => 'Le champ "statut" est obligatoire.',
            'status.enum' => 'Le champ "statut" doit être un des statuts autorisés.',
            'adresse.required' => 'Le champ "adresse" est obligatoire.',
            'adresse.string' => 'Le champ "adresse" doit être une chaîne de caractères.',
            'adresse.max' => 'Le champ "adresse" ne doit pas dépasser 255 caractères.',
            'phone.required' => 'Le champ "téléphone" est obligatoire.',
            'phone.string' => 'Le champ "téléphone" doit être une chaîne de caractères.',
            'phone.max' => 'Le champ "téléphone" ne doit pas dépasser 255 caractères.',
            'media.image' => 'La photo de profil doit être une image.',
            'media.mimes' => 'La photo de profil doit être une image au format jpeg, png, jpg, gif, svg.',
            'media.max' => 'La photo de profil ne doit pas dépasser 2048 Ko.',
            'sexe.required' => 'Le champ "sexe" est obligatoire.',
            'sexe.string' => 'Le champ "sexe" doit être une chaîne de caractères.',
            'sexe.max' => 'Le champ "sexe" ne doit pas dépasser 255 caractères.',
            'date_naissance.required' => 'Le champ "date de naissance" est obligatoire.',
            'date_naissance.string' => 'Le champ "date de naissance" doit être une chaîne de caractères.',
            'date_naissance.max' => 'Le champ "date de naissance" ne doit pas dépasser 255 caractères.',
            'postal.required' => 'Le champ "code postal" est obligatoire.',
            'postal.string' => 'Le champ "code postal" doit être une chaîne de caractères.',
            'postal.max' => 'Le champ "code postal" ne doit pas dépasser 255 caractères.',
            'ville.required' => 'Le champ "ville" est obligatoire.',
            'ville.string' => 'Le champ "ville" doit être une chaîne de caractères.',
            'ville.max' => 'Le champ "ville" ne doit pas dépasser 255 caractères.',
            'role.required' => 'Le champ "rôle" est obligatoire.',
            'role.enum' => 'Le champ "rôle" doit être un des rôles autorisés.',
            'lieux.*.exists' => 'Le lieu doit être un des lieux autorisés.',
            'lieux.*.nullable' => 'Le lieu doit être un des lieux autorisés.',
            'lieux.nullable' => 'Le lieu doit être un des lieux autorisés.',
            'lieux.array' => 'Le champ "lieux" doit être un tableau de lieux autorisés.',
            'is_manual.boolean' => 'Le champ "mode manuel" doit être un booléen.',
            'is_auto.boolean' => 'Le champ "mode automatique" doit être un booléen.',
            'password.required' => 'Le champ "mot de passe" est obligatoire.',
            'password.string' => 'Le champ "mot de passe" doit être une chaîne de caractères.',
            'password.min' => 'Le mot de passe doit contenir au moins 4 caractères.',
            'password.max' => 'Le mot de passe ne doit pas dépasser 255 caractères.',
            'password_confirmation.required' => 'Le champ "confirmation du mot de passe" est obligatoire.',
            'password_confirmation.string' => 'La confirmation du mot de passe doit être une chaîne de caractères.',
            'password_confirmation.min' => 'La confirmation du mot de passe doit contenir au moins 4 caractères.',
            'password_confirmation.max' => 'La confirmation du mot de passe ne doit pas dépasser 255 caractères.',
            'password_confirmation.same' => 'La confirmation du mot de passe doit être identique au mot de passe.',
        ];
    }
}
