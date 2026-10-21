<?php

namespace App\Models;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManeuverOccurrence extends Model
{
    use HasUuids;

    protected $fillable = [
        'maneuver_id', 'maneuver_slug', 'maneuver_name', 'reservation_id',
        'student_id', 'monitor_id', 'occurred_on', 'sequence', 'rating',
    ];

    protected $casts = ['occurred_on' => 'date:Y-m-d', 'sequence' => 'integer', 'rating' => 'integer'];

    public function maneuver(): BelongsTo { return $this->belongsTo(ManeuverCatalog::class, 'maneuver_id'); }
    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function monitor(): BelongsTo { return $this->belongsTo(Monitor::class); }
}
