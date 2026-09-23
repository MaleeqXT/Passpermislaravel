<?php

namespace App\Http\Requests\V1\Admin\Params\Zone\Zip;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreZipRequest extends FormRequest
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
            'status' => 'required|boolean',
            'code' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Le status est obligatoire',
            'status.boolean' => 'Le status doit être un booléen',
            'code.required' => 'Le code est obligatoire',
            'code.integer' => 'Le code doit être un entier',
        ];
    }
}
