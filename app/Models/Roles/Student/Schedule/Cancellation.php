<?php

namespace App\Models\Roles\Student\Schedule;

use App\Models\Media\Media;
use Database\Factories\Roles\Student\Schedule\CancellationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Cancellation extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;
    protected $with = ['media'];

    protected static function newFactory()
    {
        return CancellationFactory::new();
    }

    /**
     * @return MorphOne
     */
    public function media(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable');
    }

    /**
     * @return BelongsTo
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }
}
