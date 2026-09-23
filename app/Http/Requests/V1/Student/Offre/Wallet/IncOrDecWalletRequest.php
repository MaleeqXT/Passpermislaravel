<?php

namespace App\Http\Requests\V1\Student\Offre\Wallet;

use App\Enums\V2\Student\Schedule\Wallet\WalletStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IncOrDecWalletRequest extends FormRequest
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
            'offer_id' => 'required|exists:offers,id',
            'status' => ['required', new Enum(WalletStatusEnum::class)],
            'balance' => 'required|numeric',
        ];
    }

    public function messages()
    {
        return [
            'offer_id.required' => 'Le produit est requis.',
            'offer_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'status.required' => 'Le statut est requis.',
            'balance.required' => 'Le balance est requis.',
            'balance.numeric' => 'Le balance doit être un nombre.',
        ];
    }
}
