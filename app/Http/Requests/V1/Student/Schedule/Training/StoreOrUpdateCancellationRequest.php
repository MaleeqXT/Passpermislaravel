<?php

namespace App\Http\Requests\V1\Student\Schedule\Training;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateCancellationRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'training_id' => 'required|exists:trainings,id',
            'comment' => 'required|string'
        ];
    }

    public function messages(): array
    {
        return [
            'training_id.required' => 'La session est obligatoire.',
            'training_id.exists' => 'La session sélectionnée n\'existe pas.',
            'comment.required' => 'Le commentaire est obligatoire.',
            'comment.string' => 'Le commentaire doit être une chaîne de caractères.',
        ];
    }
}
