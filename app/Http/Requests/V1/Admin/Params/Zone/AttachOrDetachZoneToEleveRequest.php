<?php

namespace App\Http\Requests\V1\Admin\Params\Zone;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttachOrDetachZoneToEleveRequest extends FormRequest
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
            'zone_id' => 'required|exists:zones,id',
        ];
    }

    public function messages()
    {
        return [
            'zone_id.required' => 'Veuillez choisir une zone',
            'zone_id.exists' => 'La zone n\'existe pas',
        ];
    }
}
