<?php

namespace App\Http\Requests\V1\Monitor\Evaluation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentEstimatedRequest extends FormRequest
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
            'monitor_id' => 'nullable|exists:monitors,id',
            'reservation_id' => 'nullable|exists:reservations,id',
            'data' => 'required|array',

        ];
    }

    public function messages()
    {
        return [
            'data.*.date.required' => 'Le champ "date" est obligatoire.',
            'monitor_id.required' => 'Le champ "moniteur" est obligatoire.',
            'monitor_id.exists' => 'Le champ "moniteur" est invalide.',


        ];
    }
}
