<?php

namespace App\Models\Theory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TheoryAttemptAnswer extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'question_snapshot' => 'array',
        'selected_option_ids' => 'array',
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TheoryTestAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TheoryQuestion::class, 'question_id');
    }
}
