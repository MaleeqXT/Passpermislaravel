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
use Illuminate\Support\Facades\DB;
use App\Enums\V2\Student\Examen\ExamenStatusEnum;
use App\Services\SatisfactionLifecycleService;

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
     * Any staff workflow that saves the official pending exam date must use
     * the same After-survey rule.  Running this after commit keeps a failed
     * exam update from creating a survey/notification, while the lifecycle's
     * enrolment key makes rescheduling idempotent.
     */
    protected static function booted(): void
    {
        static::saved(function (self $exam): void {
            if (! $exam->student_id
                || ! $exam->date_examen
                || (int) $exam->status !== ExamenStatusEnum::PENDING->value
                || $exam->result_permis !== null) {
                return;
            }

            DB::afterCommit(function () use ($exam): void {
                $student = Student::query()->with('user')->find($exam->student_id);

                if ($student) {
                    app(SatisfactionLifecycleService::class)->evaluateAll($student);
                }
            });
        });
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
