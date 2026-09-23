<?php

namespace App\Services\Cpf;

use App\Models\Roles\Student\Cpf\Cpf;

interface CpfInterface
{

    /**
     * @param array $attributes
     * @return Cpf
     */
    public function create(array $attributes): Cpf;
}
