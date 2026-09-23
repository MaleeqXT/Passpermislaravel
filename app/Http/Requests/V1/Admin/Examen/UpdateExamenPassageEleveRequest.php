<?php

namespace App\Http\Requests\V1\Admin\Examen;

use App\Enums\V2\Student\Examen\ExamenResultPermisEnum;
use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateExamenPassageEleveRequest extends FormRequest
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
            'user_id' => 'nullable|exists:users,id',
            'date_pass_prevu' => 'nullable|string',
            'comment' => 'nullable|string',
            'date_comment' => 'nullable|string',
            'result_permis' => ['nullable', new Enum(ExamenResultPermisEnum::class)],
            'monitor_id' => 'nullable|exists:monitors,id',
            'lieu_id' => 'nullable|exists:lieux,id',
            'date_examen' => 'nullable|string',
            'date_passage' => 'nullable|string',
            'heure_passage' => 'nullable|string',
            'paiement_forfait' => 'nullable|boolean',
            'paiement_ppe' => 'nullable|boolean',
            'status' => ['nullable', new Enum(ExamenStatusEnum::class)],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'L\'utilisateur sélectionné n\'existe pas.',
            'type.string' => 'Le type doit être au format texte.',
            'date_pass_prevu.string' => 'La date de passage prévue doit être au format texte.',
            'comment.string' => 'Le commentaire doit être au format texte.',
            'result_permis.string' => 'Le résultat du permis doit être au format texte.',
            'monitor_id.exists' => 'Le Moniteur sélectionné n\'existe pas.',
            'lieu_id.exists' => 'Le lieu sélectionné n\'existe pas.',
            'date_examen.string' => 'La date de l\'examen doit être au format texte.',
            'date_passage.string' => 'La date de passage doit être au format texte.',
            'heure_passage.string' => 'L\'heure de passage doit être au format texte.',
            'status.string' => 'Le statut doit être au format texte.',
            'status.exists' => 'Le statut sélectionné n\'existe pas.',
        ];
    }
}
