<?php

namespace App\Models\Roles\Student\Schedule;

use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Student\Schedule\StudentAvailabilityFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAvailability extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;


    protected static function newFactory()
    {
        return StudentAvailabilityFactory::new();
    }
}
