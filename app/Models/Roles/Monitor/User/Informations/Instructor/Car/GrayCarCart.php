<?php

namespace App\Models\Roles\Monitor\User\Informations\Instructor\Car;

use App\Models\Media\Media;
use Database\Factories\Roles\Monitor\User\Informations\Instructor\Car\GrayCarCartFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class GrayCarCart extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected $with = ['media'];

    protected static function newFactory()
    {
        return GrayCarCartFactory::new();
    }
    /**
     * @return BelongsTo
     */
    public function car(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * @return MorphMany
     */
    public function media(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
