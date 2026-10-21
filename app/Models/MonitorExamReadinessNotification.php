<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorExamReadinessNotification extends Model
{
    use HasUuids;

    protected $fillable = [
        'exam_readiness_id',
        'secretary_user_id',
        'state_hash',
        'notification_data',
        'read_at',
    ];

    protected $casts = [
        'notification_data' => 'array',
        'read_at' => 'datetime',
    ];

    public function readiness(): BelongsTo
    {
        return $this->belongsTo(MonitorExamReadiness::class, 'exam_readiness_id');
    }

    public function secretary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretary_user_id');
    }
}
