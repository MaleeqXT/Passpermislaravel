<?php

namespace App\Http\Requests\V1\Admin\Params\Media;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreManyMediaRequest extends FormRequest
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
            'media' => 'array|max:5|min:1',
            'media.*' => 'required|mimes:jpeg,png,jpg,gif,svg,pdf,webp|max:5000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'media.required' => 'le champ images est obligatoire',
            'media.*' => 'le champ images doit être une image',
            'media.*.file' => 'le champ images doit être un fichier',
            'media.*.image' => 'le champ images doit être une image',
            'media.*.max' => 'le champ images ne doit pas dépasser 5 Mo ',

        ];
    }
}
