<?php

namespace App\Models\Roles\Monitor\Schedule;

use App\Models\DocumentEvaluation;
use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\Schedule\TrainingProposal;
use Database\Factories\Roles\Monitor\Schedule\ReservationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ReservationComment;

class Reservation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static $unguarded = true;

    protected $casts = [
        'date' => 'date:Y-m-d',
        'start_at' => 'datetime:H:i',
        'end_at' => 'datetime:H:i'
        // 'is_active' => 'boolean',
    ];

    protected static function newFactory()
    {
        return ReservationFactory::new();
    }

    /**
     * @return void
     */
    protected static function booted()
    {
        static::deleting(function ($offre) {
            $offre->trainingProposals()->delete();
        });
    }

    /**
     * @return HasMany
     */
    public function trainingProposals(): HasMany
    {
        return $this->hasMany(TrainingProposal::class);
    }


    public function comments()
    {
    return $this->hasMany(ReservationComment::class);
}

    /** The only comment needed by the calendar event card. */
    public function latestComment(): HasOne
    {
        return $this->hasOne(ReservationComment::class)->latestOfMany();
    }

    /**
     * @param Builder $query
     * @return void
     */
    private static function scopeIsNow(Builder $query): void
    {
        $query->where(function ($query) {
            $query->whereDate('date', '=', now())
                ->whereTime('end_at', '=', now()->format('H:i'));
        });
    }

    /**
     * @return BelongsTo
     */
    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }


    public function training()
    {
        return $this->hasOne(Training::class);
    }
    public function evaluation()
    {
        return $this->hasOne(DocumentEvaluation::class);
    }

    /**
     * @return BelongsTo
     */
    public function lieu(): BelongsTo
    {
        return $this->belongsTo(Lieu::class);
    }

    /**
     * @return HasOne
     */
    public function reviewMonitor(): HasOne
    {
        return $this->hasOne(ReviewMonitor::class);
    }

    /**
     * @param Builder $query
     * @return void
     */
    public function scopeIsPassed(Builder $query): void
    {
        $query->where(function ($query) {
            $query->whereDate('date', '<', now())
                ->orWhere(function ($query) {
                    $query->whereDate('date', '=', now())
                        ->whereTime('end_at', '<', now()->format('H:i'));
                });
        });
    }

    /**
     * @param Builder $query
     * @return void
     */
    public function scopeIsComme(Builder $query): void
    {
        $query->where(function ($query) {
            $query->whereDate('date', '>', now())
                ->orWhere(function ($query) {
                    $query->whereDate('date', '=', now())
                        ->whereTime('start_at', '>=', now()->format('H:i'));
                });
        });
    }
}
