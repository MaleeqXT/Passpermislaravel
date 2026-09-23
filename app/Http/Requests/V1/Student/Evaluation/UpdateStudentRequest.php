<?php

namespace App\Http\Requests\V1\Student\Evaluation;

use App\Enums\V2\Student\Cpf\DocumentCpfEtatEnum;
use App\Enums\V2\Student\Evaluation\EvaluationEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateStudentRequest extends FormRequest
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
            'dissection' => ['required', new Enum(EvaluationEnum::class)],
        ];
    }

    public function messages()
    {
        return [
            'dissection.enum' => 'Le champ "dissection" doit avoir une valeur valide.',

        ];
    }
}
