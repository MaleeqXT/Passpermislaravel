<?php

namespace App\Repository\V2\Secretary;

use App\Models\Secretary;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StoreSecretaryRepo
{
    /**
     * @param array $attributes
     * @return Model|Builder
     * @throws Exception
     */
    public static function run(array $data): Secretary
    {
        return Secretary::create($data);
    }
}
