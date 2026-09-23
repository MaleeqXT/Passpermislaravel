<?php

namespace App\Http\Requests\V1\Admin\Params\Zone;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreZoneRequest extends FormRequest
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
            'name' => 'required|unique:zones,name,' . request()->route('zone')?->id,
        ];
    }

    public function messages()
    {
        return [
            'status.required' => 'Le status est obligatoire',
            'status.boolean' => 'Le status doit être un booléen',
            'name.required' => 'Le nom est obligatoire',
            'name.unique' => 'Le nom doit être unique',
        ];
    }
}
