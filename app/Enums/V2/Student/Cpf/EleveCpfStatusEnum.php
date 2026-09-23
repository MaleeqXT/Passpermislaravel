<?php

namespace App\Enums\V2\Student\Cpf;

enum EleveCpfStatusEnum: int
{

    case Accepte = 1;
    case EnFormation = 2;
    case SortieFormation = 3;
    case ServiceFaitDeclare = 4;
    case ServiceFaitValide = 5;
    case Facture = 6;
    case KO = 7;
}
