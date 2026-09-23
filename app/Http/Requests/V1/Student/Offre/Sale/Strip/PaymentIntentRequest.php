<?php

namespace App\Http\Requests\V1\Student\Offre\Sale\Strip;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;


class PaymentIntentRequest extends FormRequest
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
            'amount' => 'required | min:1',
            'balance' => 'required | min:1',
            'student_id' => 'nullable|uuid|exists:students,id',
            'installments' => 'nullable|array',
            'installments.*.offer_id' => 'nullable|uuid',
            'installments.*.installment_no' => 'nullable|integer|min:1',
          //  'token' => 'required | string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant est requis.',
            'amount.min' => 'Le montant doit être supérieur ou égal à 1.',
            'balance.required' => 'Le balance est requis.',
            'balance.min' => 'Le balance doit être supérieur ou égal à 1.',
            'token.required' => 'Le token est requis.',
        ];
    }
}
