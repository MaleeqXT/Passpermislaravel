<?php

namespace App\Models\Roles\Student\Exam;

use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use Database\Factories\Roles\Student\Exam\StudentExamFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentExam extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected $casts = [
        'paiement_forfait' => 'boolean',
        'paiement_ppe' => 'boolean',
        'is_rdv_permis' => 'boolean',
        // 'is_auto' => 'boolean',
        'heure_passage' => 'datetime:H:i',
    ];

    protected static function newFactory()
    {
        return StudentExamFactory::new();
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
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function lieu(): BelongsTo
    {
        return $this->belongsTo(Lieu::class);
    }
}
