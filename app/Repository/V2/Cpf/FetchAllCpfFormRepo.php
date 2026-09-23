<?php

namespace App\Repository\V2\Cpf;

use App\Models\CPFDocumentInfo;
use App\Models\Roles\Monitor\User\Informations\Billing;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FetchAllCpfFormRepo
{
    /**
     * Fetch a paginated list of Reservations for a given Monitor with optional filtering.
     *
     * @param array $attributes Filtering attributes.
     * @return LengthAwarePaginator
     */
    public static function run( array $attributes = [])
    {
        return CPFDocumentInfo::query()
            ->orderByDesc('created_at')
            ->paginate();

    }

}
