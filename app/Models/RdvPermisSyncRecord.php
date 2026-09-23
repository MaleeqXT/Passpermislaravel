<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RdvPermisSyncRecord extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'synced_at' => 'datetime',
        'last_sync_attempt_at' => 'datetime',
    ];
}
