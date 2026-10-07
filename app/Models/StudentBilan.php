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

    protected $fillable = ['student_id', 'monitor_id', 'evaluations', 'hours'];

    protected $casts = [
        'evaluations' => 'array',
        'hours' => 'array',
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
