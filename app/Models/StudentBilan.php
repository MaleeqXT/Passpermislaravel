<?php

namespace App\Models;

use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBilan extends Model
{
    use HasUuids;

    protected $fillable = ['student_id', 'monitor_id', 'evaluations', 'hours', 'shared_at', 'decision_status', 'decision_at', 'student_signature'];

    protected $casts = [
        'evaluations' => 'array',
        'hours' => 'array',
        'shared_at' => 'datetime',
        'decision_at' => 'datetime',
        'student_signature' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
