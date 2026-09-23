<?php

namespace App\Models\Roles\Monitor\User\Informations\Instructor;

use App\Models\Roles\Monitor\User\Monitor;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Monitor\User\Informations\Instructor\InstructorAccountFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstructorAccount extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static $unguarded = true;

    

    protected static function newFactory()
    {
        return InstructorAccountFactory::new();
    }
    /**
     * @return BelongsTo
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
