<?php

namespace App\Models\Roles\Student\Schedule;

use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Student\User\Student;
use Database\Factories\Roles\Student\Schedule\TrainingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static $unguarded = true;

    protected $with = ['cancellation'];

    protected static function newFactory()
    {
        return TrainingFactory::new();
    }

    /**
     * @return BelongsTo
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return belongsTo
     */
    public function student(): belongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return belongsTo
     */
    public function offer(): belongsTo
    {
        return $this->belongsTo(Offer::class);
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
    public function cancellation(): HasOne
    {
        return $this->hasOne(Cancellation::class);
    }

    public function reviewMonitorEstimation()
    {
        return $this->hasOneThrough(ReviewMonitor::class, Reservation::class, 'id', 'reservation_id', 'reservation_id', 'id')
            ->where('is_estimated', true)
            ->whereNotNull('estimation');
    }
}
