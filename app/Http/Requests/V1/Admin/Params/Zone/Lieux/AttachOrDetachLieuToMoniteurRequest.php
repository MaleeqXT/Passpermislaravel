<?php

namespace App\Http\Requests\V1\Admin\Params\Zone\Lieux;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttachOrDetachLieuToMoniteurRequest extends FormRequest
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
            'lieu_id' => 'required|exists:lieux,id',
        ];
    }

    public function messages()
    {
        return [
            'lieu_id.required' => 'Veuillez choisir une lieu',
            'lieu_id.exists' => 'La zone n\'existe pas',
        ];
    }
}
