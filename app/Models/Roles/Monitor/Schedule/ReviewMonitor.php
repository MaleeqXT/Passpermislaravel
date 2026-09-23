<?php

namespace App\Models\Roles\Monitor\Schedule;

use App\Models\Roles\Monitor\User\Monitor;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Monitor\Schedule\ReviewMonitorFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewMonitor extends Model
{
    use HasFactory, HasUuids;


    protected static $unguarded = true;

    protected $with = ['monitor'];

    protected $casts = [
        'is_estimated' => 'boolean',
        'is_absent' => 'boolean',
    ];

    protected static function newFactory()
    {
        return ReviewMonitorFactory::new();
    }
    /**
     * @return BelongsTo
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
