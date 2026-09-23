<?php

namespace App\Http\Requests\V1\Student\User\Info;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Enums\V2\Admin\User\UserRolesEnum;
use App\Enums\V2\Student\User\StudentVitalBoxTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegisterStudentRequest extends FormRequest
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
            'neph' => 'nullable|unique:students,neph',
            'neph_status' => 'nullable|in:sans_neph,avec_neph',
            'date_code' => 'nullable|date',
            'how_know' => 'nullable|string',
            'email' => 'required|email|unique:users,email', // . $this->route('user'),
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            // 'status' => ['nullable',new Enum(SituationStatusEnum::class)],
            'adresse' => 'nullable|string|max:255',
            'phone' => 'required|string|max:255',
            'media' => 'nullable',
            'sexe' => 'nullable|string|max:255',
            'date_naissance' => 'nullable|string|max:255',
            'postal' => 'nullable|string|max:255',
            'postal_code_1' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'documents' => 'nullable|array|max:10',
            'documents.*.type' => 'required_with:documents|string|max:120',
            'documents.*.files' => 'required_with:documents.*.type|array|min:1|max:10',
            'documents.*.files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',

        ];
    }

    public function messages()
    {
        return [
            'neph.required' => 'Le NEPH est requis.',
            'neph.integer' => 'Le NEPH doit être un nombre.',
            'neph.min' => 'Le NEPH doit comporter au moins 12 chiffres.',
            'neph.unique' => 'Le NEPH est déjà utilisé.',
            'neph_status.in' => 'Le statut NEPH sélectionné est invalide.',
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'L\'email est déjà utilisé.',
            'first_name.required' => 'Le prénom est requis.',
            'last_name.required' => 'Le nom est requis.',
            // Ajoutez d'autres messages d'erreur si nécessaire
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit comporter au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password_confirmation.required' => 'La confirmation du mot de passe est requise.',
            'password_confirmation.min' => 'La confirmation du mot de passe doit comporter au moins 8 caractères.',
            'how_know.required' => 'La source est requise.',
            'how_know.string' => 'La source doit être une chaîne de caractères.',
            'adresse.required' => 'L\'adresse est requise.',
            'adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'adresse.max' => 'L\'adresse ne doit pas dépasser 255 caractères.',
            'phone.required' => 'Le téléphone est requis.',
            'phone.string' => 'Le téléphone doit être une chaîne de caractères.',
            'phone.max' => 'Le téléphone ne doit pas dépasser 255 caractères.',
            'sexe.required' => 'Le sexe est requis.',
            'sexe.string' => 'Le sexe doit être une chaîne de caractères.',

            'sexe.max' => 'Le sexe ne doit pas dépasser 255 caractères.',
            'date_naissance.required' => 'La date de naissance est requise.',
            'date_naissance.string' => 'La date de naissance doit être une chaîne de caractères.',
            'date_naissance.max' => 'La date de naissance ne doit pas dépasser 255 caractères.',
            'postal.required' => 'Le code postal est requis.',
            'postal.string' => 'Le code postal doit être une chaîne de caractères.',
            'postal.max' => 'Le code postal ne doit pas dépasser 255 caractères.',
            'postal_code_1.string' => 'Le code postal 1 doit être une chaîne de caractères.',
            'postal_code_1.max' => 'Le code postal 1 ne doit pas dépasser 255 caractères.',
            'ville.required' => 'La ville est requise.',
            'ville.string' => 'La ville doit être une chaîne de caractères.',
            'ville.max' => 'La ville ne doit pas dépasser 255 caractères.',

            'status.required' => 'Le statut est requis.',
            'status.enum' => 'Le statut doit être un des statuts autorisés.',

        ];
    }
}
