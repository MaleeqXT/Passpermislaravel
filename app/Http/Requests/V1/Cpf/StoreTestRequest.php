<?php

namespace App\Http\Requests\V1\Cpf;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTestRequest extends FormRequest
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
            'name' => 'required|string',
            'date_naissance' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string',
            'type' => 'required|string',
            'first' => 'required|array',
            'two' => 'required|array',
            'two_feedback' => 'nullable|string',
            'three' => 'required|array',
            'four' => 'required|array',
            'five' => 'required|array',
            'sex' => 'required|array',
            'seven' => 'required|array',
            'eight' => 'required|array',
            'nine' => 'required|array',
            'ten' => 'required|array',
            'eleven' => 'required|array',
            'twelve' => 'required|array',
            'commentaire_formateur' => 'required|string',
            'nb_heur' => 'nullable|integer',
            'date_evaluation' => 'required|string',
            'signature' => 'required|string',

        ];
    }

    public function messages()
    {
        return [
           'name'=>'Le champ  est obligatoire.',
            'date_naissance'=>'Le champ  est obligatoire.',
            'phone'=>'Le champ  est obligatoire.',
            'email'=>'Le champ  est obligatoire.',
            'type'=>'Le champ  est obligatoire.',
            'first'=>'Le champ  est obligatoire.',
            'two'=>'Le champ  est obligatoire.',
            'two_feedback'=>'Le champ  est obligatoire.',
            'three'=>'Le champ  est obligatoire.',
            'four'=>'Le champ  est obligatoire.',
            'five'=>'Le champ  est obligatoire.',
            'sex'=>'Le champ  est obligatoire.',
            'seven'=>'Le champ  est obligatoire.',
            'eight'=>'Le champ  est obligatoire.',
            'nine'=>'Le champ  est obligatoire.',
            'ten'=>'Le champ  est obligatoire.',
            'eleven'=>'Le champ  est obligatoire.',
            'twelve'=>'Le champ  est obligatoire.',
            'commentaire_formateur'=>'Le champ  est obligatoire.',
            'nb_heur'=>'Le champ  est obligatoire.',
            'date_evaluation'=>'Le champ  est obligatoire.',
            'signature'=>'Le champ  est obligatoire.',



        ];
    }
}
