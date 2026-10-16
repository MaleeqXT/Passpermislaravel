<?php

namespace App\Models;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrivingLessonMilestoneAlert extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'completed_minutes' => 'integer',
        'milestone_hour' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
