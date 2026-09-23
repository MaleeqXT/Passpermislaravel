<?php

namespace App\Http\Requests\V1\Student\Offre\Sale;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RefundSaleRequest extends FormRequest
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
            'offer_id' => 'nullable|exists:offers,id|string'
        ];
    }

    public function messages()
    {
        return [
            'offer_id.required' => 'Le produit est obligatoire.',
            'offer_id.exists' =>  'Le produit n\'existe pas.',
        ];
    }
}
