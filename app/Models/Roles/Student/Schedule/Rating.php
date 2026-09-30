<?php

namespace App\Models\Roles\Student\Schedule;

use App\Models\Roles\Student\Schedule\CompetencyEvaluationDetail;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Student\Schedule\RatingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Competency\Competency;

class Rating extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected static function newFactory()
    {
        return RatingFactory::new();
    }

    public function evaluationDetail(): HasOne
    {
        return $this->hasOne(CompetencyEvaluationDetail::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }
}
