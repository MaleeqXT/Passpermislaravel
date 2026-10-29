<?php

namespace App\Models;

use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Immutable snapshot of one paid driving purchase for satisfaction milestones. */
class SatisfactionEnrollment extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'purchased_minutes' => 'integer',
        'is_first_hour' => 'boolean',
        'is_driving_package' => 'boolean',
    ];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function offer(): BelongsTo { return $this->belongsTo(Offer::class); }
    public function trainings(): HasMany { return $this->hasMany(\App\Models\Roles\Student\Schedule\Training::class, 'satisfaction_enrollment_id'); }
}
