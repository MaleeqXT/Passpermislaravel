<?php

namespace App\Models\Theory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class TheorySeries extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [];

    public function seriesQuestions(): HasMany
    {
        return $this->hasMany(TheorySeriesQuestion::class, 'series_id')->orderBy('sort_order');
    }

    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(
            TheoryQuestion::class,
            TheorySeriesQuestion::class,
            'series_id',
            'id',
            'id',
            'question_id'
        )->orderBy('theory_series_questions.sort_order');
    }
}
