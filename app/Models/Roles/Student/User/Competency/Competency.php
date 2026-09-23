<?php

namespace App\Models\Roles\Student\User\Competency;

use App\Models\Roles\Student\Schedule\Rating;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Student\User\Competency\CompetencyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Competency extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;
    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function newFactory()
    {
        return CompetencyFactory::new();
    }

    /**
     * @return BelongsTo
     */
    public function mainCompetency(): BelongsTo
    {
        return $this->belongsTo(MainCompetency::class);
    }

    /**
     * @return HasOne
     */
    public function rating(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Rating::class);
    }
}
