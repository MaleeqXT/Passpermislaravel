<?php

namespace App\Models\Theory;

use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TheoryTestAttempt extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(TheorySeries::class, 'series_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TheoryAttemptAnswer::class, 'attempt_id');
    }
}
