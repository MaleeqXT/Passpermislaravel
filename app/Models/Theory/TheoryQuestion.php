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

    public function contentBank(): BelongsTo
    {
        return $this->belongsTo(TheoryContentBank::class, 'content_bank_id');
    }

    public function scopeForActiveStudentBank($query)
    {
        return $query->whereHas('contentBank', fn ($bank) => $bank->where('status', 'active')->where('is_active', true));
    }

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

    public function parts(): HasMany
    {
        return $this->hasMany(TheoryQuestionPart::class, 'question_id')->orderBy('sort_order');
    }
}
