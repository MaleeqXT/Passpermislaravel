<?php

namespace App\Http\Requests\V1\Admin\Params\Media;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOneMediaRequest extends FormRequest
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
            'media' => 'required | mimes:jpeg,png,jpg,gif,svg,pdf,webp',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'media.required' => 'le champ images est obligatoire ',
            'media.file' => 'le champ images doit être un fichier',
            'media.image' => 'le champ images doit être une image',
            'media.max' => 'le champ images ne doit pas dépasser 5000 octets',
            'media.mimes' => 'le champ images doit être de type jpeg,png,jpg,gif,svg,pdf',


        ];
    }
}
