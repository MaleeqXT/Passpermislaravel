<?php

namespace App\Services\LivretNumerique;

use RuntimeException;

class ProviderSchemaUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Le schéma Swagger Livret Numérique actuel n’est pas encore vérifié. Aucune requête fournisseur n’a été envoyée.');
    }
}
