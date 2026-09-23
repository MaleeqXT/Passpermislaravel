<?php

namespace App\Models\Media;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageMedia extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded= true;


    /**
     * @return HasMany
     */
    public function media(): hasMany
    {
        return $this->hasMany(Media::class);
    }
}
