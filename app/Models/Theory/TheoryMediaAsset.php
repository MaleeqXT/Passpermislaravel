<?php

namespace App\Models\Theory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TheoryMediaAsset extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function contentBank(): BelongsTo
    {
        return $this->belongsTo(TheoryContentBank::class, 'content_bank_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TheoryQuestion::class, 'question_id');
    }
}
