<?php

namespace App\Http\Requests\V1\Student\Cpf;

use App\Enums\V2\Student\Cpf\DocumentCpfEtatEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DocumentCpfRequest extends FormRequest
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
            'document' => ['required', new Enum(DocumentCpfEtatEnum::class)],
            'data' => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'document.enum' => 'Le champ "document" doit avoir une valeur valide.',
            'data.array' => 'Le champ "data" doit être un tableau.',

        ];
    }
}
