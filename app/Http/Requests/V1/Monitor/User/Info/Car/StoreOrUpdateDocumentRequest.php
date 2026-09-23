<?php

namespace App\Http\Requests\V1\Monitor\User\Info\Car;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateDocumentRequest extends FormRequest
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
            'media_diplom' => 'nullable|array',
            'media_diplom.*' => 'required|string',
            'media_permis' => 'nullable|array',
            'media_permis.*' => 'required|string',
            'media_piece_identite' => 'nullable|array',
            'media_piece_identite.*' => 'required|string',

        ];
    }

    public function messages()
    {
        return [
            'media_diplom.*.required' => 'Each diploma media is required.',
            'media_diplom.*.string' => 'Each diploma media must be a valid string.',
            'media_permis.*.required' => 'Each permit media is required.',
            'media_permis.*.string' => 'Each permit media must be a valid string.',
            'media_piece_identite.*.required' => 'Each identity document media is required.',
            'media_piece_identite.*.string' => 'Each identity document media must be a valid string.',
        ];
    }
    public function attributes()
    {
        return [
            'media_diplom' => 'diploma media',
            'media_diplom.*' => 'each diploma media',
            'media_permis' => 'permit media',
            'media_permis.*' => 'each permit media',
            'media_piece_identite' => 'identity piece media',
            'media_piece_identite.*' => 'each identity piece media',
        ];
    }
}
