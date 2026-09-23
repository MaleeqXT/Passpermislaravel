<?php

namespace App\Http\Requests\V1\Student\Cpf\EleveCpf;

use App\Enums\V2\Student\Cpf\EleveCpfStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCpfEleveRequest extends FormRequest
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
            'date_verif' => 'nullable|date',
            'comment' => 'nullable|string',
            'status' => ['nullable', new Enum(EleveCpfStatusEnum::class)],
        ];
    }
    public function messages()
    {
        return [
            'date_verif.date' => 'Le champ "date de vérification" doit être une date valide.',
            'comment.string' => 'Le champ "commentaire" doit être une chaîne de caractères.',
            'status.enum' => 'Le champ "status" doit avoir une valeur valide.',
        ];
    }
}
