<?php

namespace App\Models\Roles\Monitor\User\Informations\Instructor;

use App\Models\Media\Media;
use Database\Factories\Roles\Monitor\User\Informations\Instructor\InstructorPermissionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class InstructorPermission extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected $with = ['media'];


    protected static function newFactory()
    {
        return InstructorPermissionFactory::new();
    }

    /**
     * @return BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(InstructorDocument::class);
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
