<?php

namespace App\Models\Roles\Monitor\User\Informations;

use App\Models\Roles\Monitor\User\Monitor;
use Database\Factories\Roles\Monitor\User\Informations\BillingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Billing extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static $unguarded = true;

    protected $casts = [
        'details' => 'array',
        'status' => 'boolean'
    ];

    protected $with = ['monitor.user', 'monitor.lieux.zone', 'monitor.instructorDocument'];

    protected static function newFactory()
    {
        return BillingFactory::new();
    }
    /**
     * @return BelongsTo
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
