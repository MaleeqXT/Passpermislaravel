<?php

namespace App\Models;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentEvaluation extends Model
{
    use  HasUuids;

    protected static $unguarded = true;

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    /**
     * @return BelongsTo
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
    // /**
    //  * @return BelongsTo
    //  */
    // public function reservation(): BelongsTo
    // {
    //     return $this->belongsTo(Reservation::class);
    // }
}
