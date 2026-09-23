<?php

namespace App\Repository\V2\Student\Cpf;

use App\Models\Roles\Student\Cpf\Cpf;
use App\Models\Roles\Student\Cpf\CpfVerificationDocument;
use Illuminate\Database\Eloquent\Model;

class FetchCpfStudentRepo
{
    /**
     * @param Cpf $cpf
     * @param array|null $attributes
     * @return CpfVerificationDocument|Model
     */
    public static function run(Cpf $cpf, array $attributes = null): CpfVerificationDocument|Model
    {
        return $cpf->load(['documentQuestionnaireEntreFormation', 'documentAttestationHonneur', 'documentAttestationFinFormation', 'documentQuestionnaireSatisfaction', 'documentSuiviPro', 'documentSuiviPro2']);
    }
}
