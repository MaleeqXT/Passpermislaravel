<?php

namespace App\Models;

use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivretLesson extends Model
{
    use HasUuids;

    public const TYPES = ['circulation', 'plateau', 'simulator', 'theoretical'];
    protected $guarded = [];
    protected $casts = ['lesson_date' => 'date', 'started_at' => 'datetime', 'ended_at' => 'datetime', 'synced_at' => 'datetime', 'last_sync_attempt_at' => 'datetime', 'archived_at' => 'datetime'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function contract(): BelongsTo { return $this->belongsTo(LivretTrainingContract::class, 'training_contract_id'); }
    public function syncUser(): BelongsTo { return $this->belongsTo(User::class, 'sync_user_id'); }
}
