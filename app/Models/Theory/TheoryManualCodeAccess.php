<?php

namespace App\Models\Theory;

use App\Models\Roles\Student\User\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TheoryManualCodeAccess extends Model
{
    use HasUuids;

    protected $table = 'theory_manual_accesses';

    protected $guarded = [];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    /** Access is valid until it is revoked or its optional expiry date passes. */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where(fn (Builder $access) => $access->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }
}
