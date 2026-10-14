<?php

namespace App\Models\Theory;

use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TheoryStudentProgress extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = ['last_answered_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TheoryCategory::class, 'category_id');
    }
}
