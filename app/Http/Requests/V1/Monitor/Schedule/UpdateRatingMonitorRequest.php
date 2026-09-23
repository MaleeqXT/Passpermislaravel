<?php

namespace App\Http\Requests\V1\Monitor\Schedule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRatingMonitorRequest extends FormRequest
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
            'comment' => 'nullable|string',
            'estimation' => 'nullable|integer',
            'is_absent' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'comment.string' => 'Le champ "commentaire" doit être une chaîne de caractères.',
            'estimation.integer' => 'Le champ "estimation" doit être un entier.',
            'estimation.max' => 'Le champ "estimation" doit être inférieur ou égal à 25.',
        ];
    }
}
