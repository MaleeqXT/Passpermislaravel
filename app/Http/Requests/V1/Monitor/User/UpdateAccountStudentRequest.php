<?php

namespace App\Http\Requests\V1\Monitor\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountStudentRequest extends FormRequest
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
            'aval_monitor' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'aval_monitor.required' => 'Le champ "aval_monitor" est obligatoire',
            'aval_monitor.boolean' => 'Le champ "aval_monitor" doit être un booléen',



        ];
    }
}
