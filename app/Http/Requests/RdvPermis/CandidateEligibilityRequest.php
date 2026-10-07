<?php

namespace App\Http\Requests\RdvPermis;

use Illuminate\Foundation\Http\FormRequest;

class CandidateEligibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route retains Sanctum and administrative-role authorization.
    }

    public function rules(): array
    {
        return [
            'filtre' => ['required', 'array:creneauId,nom,numeroDossier'],
            'filtre.creneauId' => ['required', 'string', 'min:1'],
            'filtre.nom' => ['sometimes', 'array:query,match'],
            'filtre.nom.query' => ['sometimes', 'string'],
            'filtre.nom.match' => ['sometimes', 'in:PARTIAL,EXACT'],
            'filtre.numeroDossier' => ['sometimes', 'array:query,match'],
            'filtre.numeroDossier.query' => ['sometimes', 'string'],
            'filtre.numeroDossier.match' => ['sometimes', 'in:PARTIAL,EXACT'],
        ];
    }
}
