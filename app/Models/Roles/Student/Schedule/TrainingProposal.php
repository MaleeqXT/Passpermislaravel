<?php

namespace App\Models\Roles\Student\Schedule;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\User\Student;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Student\Schedule\TrainingProposalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProposal extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];


    protected $casts = [
        'status' => 'integer',
        
    ];

    protected static function newFactory()
    {
        return TrainingProposalFactory::new();
    }


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
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }



}
