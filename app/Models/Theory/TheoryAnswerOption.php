<?php

namespace App\Models\Theory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TheoryAnswerOption extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = ['is_correct' => 'boolean'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(TheoryQuestion::class, 'question_id');
    }
}
