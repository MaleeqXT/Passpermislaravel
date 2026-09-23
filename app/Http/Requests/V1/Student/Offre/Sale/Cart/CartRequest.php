<?php

namespace  app\Http\Requests\V1\Student\Offre\Sale\Cart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
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
        'data' => 'required|array',
        'data.*.offer_id' => 'required|exists:offers,id',
        'data.*.quantity' => 'required|integer|min:1',
        'data.*.tranches' => 'required|integer|min:0',
    ];
}

public function messages()
{
    return [
        'data.required' => 'Les données sont requises.',
        'data.array' => 'Les données doivent être un tableau.',

        'data.*.offer_id' => 'L\'identifiant de l\'offre est requis.',
        'data.*.offer_id.required' => 'L\'identifiant de l\'offre est requis.',
        'data.*.offer_id.exists' => 'L\'offre sélectionnée est introuvable.',

        'data.*.quantity.required' => 'La quantité est requise.',
        'data.*.quantity.integer' => 'La quantité doit être un entier.',
        'data.*.quantity.min' => 'La quantité doit être supérieure à 0.',

        'data.*.tranches.required' => 'Le nombre de tranches est requis.',
        'data.*.tranches.integer' => 'Le nombre de tranches doit être un entier.',
        'data.*.tranches.min' => 'Le nombre de tranches doit être supérieur ou égal à 0.',


    ];
}

}
