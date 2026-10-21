<?php

namespace App\Models\Roles\Monitor\Planning;

use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorCalendarSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'monitor_id',
        'encrypted_token',
        'token_hash',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
