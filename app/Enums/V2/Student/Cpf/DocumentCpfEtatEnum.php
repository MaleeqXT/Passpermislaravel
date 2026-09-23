<?php

namespace App\Enums\V2\Student\Cpf;

enum DocumentCpfEtatEnum: int
{

    case questionnaire_entre_formation = 1;
    case attestation_honneur = 2;
    case attestation_fin_formation = 3;
    case questionnaire_satisfaction = 4;
        # after 1 month
    case suivi_pro = 5;
        # after 2 months
    case suivi_pro2 = 6;
}
