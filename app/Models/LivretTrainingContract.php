<?php

namespace App\Models;

use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LivretTrainingContract extends Model
{
    use HasUuids;

    protected $guarded = [];
    protected $casts = ['registration_date' => 'date', 'synced_at' => 'datetime', 'last_sync_attempt_at' => 'datetime', 'archived_at' => 'datetime'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function syncUser(): BelongsTo { return $this->belongsTo(User::class, 'sync_user_id'); }
    public function lessons(): HasMany { return $this->hasMany(LivretLesson::class, 'training_contract_id'); }
}
