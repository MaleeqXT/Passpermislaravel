<?php

namespace App\Models\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Monitor\User\Monitor;
use Database\Factories\Roles\Monitor\User\Informations\Instructor\InstructorDocumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InstructorDocument extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected static function newFactory()
    {
        return InstructorDocumentFactory::new();
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
    public function instructorPermission(): HasOne
    {
        return $this->hasOne(InstructorPermission::class);
    }
}
