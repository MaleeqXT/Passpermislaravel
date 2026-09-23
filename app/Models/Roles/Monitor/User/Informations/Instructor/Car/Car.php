<?php

namespace App\Models\Roles\Monitor\User\Informations\Instructor\Car;

use App\Models\Roles\Monitor\User\Monitor;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Monitor\User\Informations\Instructor\Car\CarFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Car extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;
    protected $casts = [
        'is_auto' => 'boolean'
    ];

    protected static function newFactory()
    {
        return CarFactory::new();
    }

    /**
     * @return BelongsTo
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * @return HasOne
     */
    public function grayCarCart(): HasOne
    {
        return $this->hasOne(GrayCarCart::class);
    }

    /**
     * @return HasOne
     */
    public function assurance(): HasOne
    {
        return $this->hasOne(CarAssurance::class);
    }
}
