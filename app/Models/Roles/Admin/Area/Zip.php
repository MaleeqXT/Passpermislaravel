<?php

namespace App\Models\Roles\Admin\Area;

use Database\Factories\Roles\Admin\Area\ZipFactory;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zip extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected static function newFactory()
    {
        return ZipFactory::new();
    }

    protected $casts = [
        'status' => 'boolean'
    ];


    /**
     * @return BelongsTo
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
