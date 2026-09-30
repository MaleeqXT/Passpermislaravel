<?php

namespace App\Models\Roles\Student\Schedule;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CompetencyEvaluationDetail extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'student_evaluation' => 'boolean',
    ];
}
