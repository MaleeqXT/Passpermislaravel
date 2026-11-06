<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LivretSyncLog extends Model
{
    use HasUuids;
    public const UPDATED_AT = null;
    protected $guarded = [];
}
