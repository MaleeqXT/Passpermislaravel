<?php

namespace App\Models\Roles\Student\Schedule;

use App\Models\Roles\Monitor\User\Monitor;
use Database\Factories\Roles\Student\Schedule\ScheduleSettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSetting extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected $casts = [
        'days' => 'array',
    ];

    protected static function newFactory()
    {
        return ScheduleSettingFactory::new();
    }

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
