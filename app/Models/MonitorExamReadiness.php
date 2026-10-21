<?php

namespace App\Models;

use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitorExamReadiness extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'monitor_id',
        'is_ready',
        'additional_hours',
        'practice_frequency',
        'observation',
        'ready_at',
        'notification_sequence',
    ];

    protected $casts = [
        'is_ready' => 'boolean',
        'additional_hours' => 'integer',
        'ready_at' => 'datetime',
        'notification_sequence' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(MonitorExamReadinessNotification::class, 'exam_readiness_id');
    }
}
