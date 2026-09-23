<?php

namespace App\Http\Requests\V1\Monitor\Lieux;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AsyncLieuxMoniteurRequest extends FormRequest
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
            'lieux' => 'required|array',
            'lieux.*' => 'required|exists:lieux,id',
        ];
    }

    public function messages()
    {
        return [
            'lieux.required' => 'Veuillez sélectionner les lieux.',
            'lieux.*.exists' => 'Le lieu ":input" n\'existe pas.',
        ];
    }
}
