<?php

namespace App\Models\Roles\Monitor\User\Informations;

use App\Models\Roles\Monitor\User\Monitor;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Monitor\User\Informations\MonitorInformationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonitorInformation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static $unguarded = true;

    protected $casts = [
        'is_auto' => 'boolean'
    ];

    protected static function newFactory()
    {
        return MonitorInformationFactory::new();
    }

    /**
     * @return BelongsTo
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
