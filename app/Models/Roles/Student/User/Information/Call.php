<?php

namespace App\Models\Roles\Student\User\Information;

use App\Models\Roles\Student\User\Student;
use App\Models\User;
use Database\Factories\Roles\Student\User\Information\CallFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected static function newFactory()
    {
        return CallFactory::new();
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
