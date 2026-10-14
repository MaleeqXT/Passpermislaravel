<?php

namespace App\Models\Theory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TheoryQuestion extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_review' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TheoryCategory::class, 'category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(TheoryAnswerOption::class, 'question_id')->orderBy('sort_order');
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(TheoryAttemptAnswer::class, 'question_id');
    }
}
