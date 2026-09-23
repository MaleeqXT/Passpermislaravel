<?php

namespace App\Http\Requests\V1\Admin\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
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
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Veuillez renseigner votre mot de passe.',
            'password.string' => 'Le mot de passe doit être au format texte.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.max' => 'Le mot de passe ne doit pas dépasser 255 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password_confirmation.required' => 'Veuillez confirmer votre mot de passe.',
            'password_confirmation.string' => 'La confirmation du mot de passe doit être au format texte.',
            'password_confirmation.min' => 'La confirmation du mot de passe doit contenir au moins 8 caractères.',
            'password_confirmation.max' => 'La confirmation du mot de passe ne doit pas dépasser 255 caractères.',
        ];
    }
}
