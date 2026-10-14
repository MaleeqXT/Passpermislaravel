<?php

namespace App\Models\Theory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TheoryImportBatch extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
