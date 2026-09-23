<?php

namespace App\Http\Requests\V1\Monitor\User;
// use App\Enums\V2\General\StatusEnum;
use App\Enums\V2\Admin\User\UserRolesEnum;
use App\Enums\V2\Monitor\MonitorSituationStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrUpdateMonitorRequest extends FormRequest
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
            'status' => [new Enum(MonitorSituationStatusEnum::class)],
            'is_manual' => 'nullable|boolean',
            'is_auto' => 'nullable|boolean',
            'adresse' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            // 'media' => 'required|string|max:255',
            'media' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sexe' => 'nullable|string|max:255',
            'date_naissance' => 'required|string|max:255',
            'postal' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'password_confirmation' => 'nullable|string|min:8',
            // 'role' => ['required', new Enum(UserRolesEnum::class)],
            'lieux' => 'nullable|array',
            'lieux.*' => 'nullable|exists:lieux,id',
            'experience' => 'nullable|integer',
            'dernier_experience' => 'nullable|string',
            'details_experience' => 'nullable|string',
            'departement' => 'nullable|string',
            'numero_autorisation' => 'nullable|string',
            'tarif_enseignement' => 'nullable|string',
            'tarif_car' => 'nullable|string',
            'iban' => 'nullable|string|max:255',
            'bic' => 'nullable|string|max:255',
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
            'status.enum' => 'Le champ "statut" doit être une valeur valide.',
            'adresse.required' => 'Le champ "adresse" est obligatoire.',
            'adresse.string' => 'Le champ "adresse" doit être une chaîne de caractères.',
            'adresse.max' => 'Le champ "adresse" ne doit pas dépasser 255 caractères.',
            'phone.required' => 'Le champ "téléphone" est obligatoire.',
            'phone.string' => 'Le champ "téléphone" doit être une chaîne de caractères.',
            'phone.max' => 'Le champ "téléphone" ne doit pas dépasser 255 caractères.',
            'media.required' => 'L\'image est obligatoire.',
            'media.string' => 'La photo de profil ne doit pas dépasser 255 caractères.',
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
            'lieux.*.exists' => 'Le lieu doit être un des lieux autorisés.',
            'lieux.*.nullable' => 'Le lieu doit être un des lieux autorisés.',
            'lieux.nullable' => 'Le lieu doit être un des lieux autorisés.',
            'lieux.array' => 'Le lieu doit être un tableau de lieux autorisés.',
            'is_manual.boolean' => 'Le mode manuel doit être un booléen.',
            'is_auto.boolean' => 'Le mode automatique doit être un booléen.',
            'password.required' => 'Le champ "mot de passe" est obligatoire.',
            'password.string' => 'Le champ "mot de passe" doit être une chaîne de caractères.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password_confirmation.required' => 'Le champ "confirmation du mot de passe" est obligatoire.',
            'password_confirmation.string' => 'La confirmation du mot de passe doit être une chaîne de caractères.',
            'password_confirmation.min' => 'La confirmation du mot de passe doit contenir au moins 8 caractères.',
            'role.required' => 'Le champ "rôle" est obligatoire.',
            'role.enum' => 'Le champ "rôle" doit être un des rôles autorisés.',
            'departement.required' => 'Le champ "département" est obligatoire.',
            'departement.string' => 'Le champ "département" doit être une chaîne de caractères.',
            'numero_autorisation.required' => 'Le champ "numéro d\'autorisation" est obligatoire.',
            'numero_autorisation.string' => 'Le champ "numéro d\'autorisation" doit être une chaîne de caractères.',
            'tarif_enseignement.required' => 'Le champ "tarif d\'enseignement" est obligatoire.',
            'tarif_enseignement.string' => 'Le champ "tarif d\'enseignement" doit être une chaîne de caractères.',
            'tarif_car.required' => 'Le champ "tarif de la voiture" est obligatoire.',
            'tarif_car.string' => 'Le champ "tarif de la voiture" doit être une chaîne de caractères.',
        ];
    }
}
