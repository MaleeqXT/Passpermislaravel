<?php

namespace App\Http\Requests\V1\Admin\Contact;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateContactRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'message' => 'required|string',
            'subject' => 'required|string'
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Veuillez entrer votre nom.',
            'prenom.required' => 'Veuillez entrer votre prénom.',
            'email.required' => 'Veuillez entrer votre adresse email.',
            'email.email' => 'Veuillez fournir une adresse email valide.',
            'phone.required' => 'Veuillez entrer votre numéro de téléphone.',
            'message.required' => 'Veuillez écrire un message.',
            'subject.required' => 'Veuillez préciser le sujet.',
        ];
    }
}
