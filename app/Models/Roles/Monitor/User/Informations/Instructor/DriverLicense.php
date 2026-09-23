<?php

namespace App\Models\Roles\Monitor\User\Informations\Instructor;

use App\Models\Media\Media;
use App\Models\Roles\Monitor\User\Monitor;
use Database\Factories\Roles\Monitor\User\Informations\Instructor\DriverLicenseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DriverLicense extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected $with = ['media'];


    protected static function newFactory()
    {
        return DriverLicenseFactory::new();
    }
    /**
     * @return BelongsTo
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * @return MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->whereHas('storageMedia', function ($query) {
            $query->where('is_active', true);
        });
    }
}
