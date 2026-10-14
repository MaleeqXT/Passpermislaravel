<?php

namespace App\Models\Theory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TheorySeriesQuestion extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function series(): BelongsTo
    {
        return $this->belongsTo(TheorySeries::class, 'series_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TheoryQuestion::class, 'question_id');
    }
}
