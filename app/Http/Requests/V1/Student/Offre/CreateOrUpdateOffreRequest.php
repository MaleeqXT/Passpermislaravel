<?php

namespace App\Http\Requests\V1\Student\Offre;

use App\Enums\V2\Student\Schedule\Offre\OffreTypeEnum;
use App\Enums\V2\Student\Schedule\Offre\OffreTypeStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateOrUpdateOffreRequest extends FormRequest
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
            'name' => 'required|max:255',
            'description' => 'required|min:3',
            'status' => 'required|boolean',
            'media' => 'nullable|image|max:5120',
            'caracteristiques' => 'nullable |string | min:3',
        'agency_name' => ['nullable', 'string', 'max:255'], // ✅ NEW
        'second_price' => ['nullable', 'numeric'],   // ✅ NEW
            'price_ht' => 'nullable | numeric',
            'original_price' => 'nullable | numeric',
            'discounted_price' => 'nullable | numeric',
            'final_price' => 'nullable | numeric',
            'balance' => 'nullable | numeric',
            'balance_2' => ['nullable', 'numeric'],      // ✅
            'multi_payment' => 'nullable | numeric',
            'is_auto' => 'required | boolean',
            'is_cpf' => 'required | boolean',
            'agency_pricing' => ['nullable', 'array'],
            // you can add more granular rules for each element if needed, e.g. 'agency_pricing.*.price_ht' => 'nullable|numeric'
            'type' => ['required', new Enum(OffreTypeStatusEnum::class)],
            'type_offre' => ['required', new Enum(OffreTypeEnum::class)],
            'color' => 'required | max:255',
            'is_evaluation' => 'nullable | boolean',
            'order' => 'nullable | integer',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Le nom est requis.',
            'description.required' => 'La description est requise.',
            'status.required' => 'Le statut est requis.',
            'media.required' => 'Le média est requis.',
            'caracteristiques.required' => 'Les caractéristiques sont requises.',
            'original_price.required' => 'Le prix TTC est requis.',
            'price_ht.required' => 'Le prix HT est requis.',
            'balance.required' => 'Le balance est requis.',
            'multi_payment.required' => 'Le paiement multiple est requis.',
            'is_auto.required' => 'La boîte automatique est requise.',
            'is_cpf.required' => 'Le CPF est requis.',
            'agency_name' => 'La Agnecy est requis', // ✅ NEW
            'second_price' => 'Le Prix Secondary est requis',
             'balance_2' => 'Le Balance Secondary est requis',   // ✅ NEW
            'type.required' => 'Le type est requis.',
            'type_offre.required' => 'Le type d\'offre est requis.',
            'color.required' => 'La couleur est requise.',
            'is_evaluation.required' => 'L\'évaluation est requise.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'description.min' => 'La description doit comporter au moins 3 caractères.',
            'caracteristiques.min' => 'Les caractéristiques doivent comporter au moins 3 caractères.',
            'price_ht.numeric' => 'Le prix HT doit être un nombre.',
            'original_price.numeric' => 'Le prix TTC doit être un nombre.',
            'discounted_price.numeric' => 'Le prix remisé doit être un nombre.',
            'final_price.numeric' => 'Le prix final doit être un nombre.',
            'balance.numeric' => 'Le solde doit être un nombre.',
            'multi_payment.numeric' => 'Le paiement multiple doit être un nombre.',
            'is_auto.boolean' => 'La boîte automatique doit être un booléen.',
            'is_cpf.boolean' => 'Le CPF doit être un booléen.',
            'agency_pricing.array' => 'La tarification d\'agence doit être un tableau.',
            'type_offre.string' => 'Le type d\'offre doit être une chaîne de caractères.',
            'color.max' => 'La couleur ne doit pas dépasser 255 caractères.',
            'is_evaluation.boolean' => 'L\'évaluation doit être un booléen.',
            'status.boolean' => 'Le statut doit être un booléen.',
            'media.file' => 'Le média doit être un fichier.',
            'media.image' => 'Le média doit être une image.',
            'media.max' => 'Le média ne doit pas dépasser 5000 octets.',
            'caracteristiques.string' => 'Les caractéristiques doivent être une chaîne de caractères.',
            'order.integer' => 'L\'ordre doit être un entier.',
            'order.numeric' => 'L\'ordre doit être un nombre.',
            'order.required' => 'L\'ordre est requis.',
            'order.min' => 'L\'ordre doit être supérieur ou égal à 0.',

        ];
    }
}
