<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use app\Models\Roles\Monitor\User\Monitor;
class ReservationRequest extends Model
{
protected $fillable = [
        'monitor_id',
        'hours_periods',
        'comment',
        'status',
    ];



    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

      public function getHoursPeriodsAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    // Mutator to convert array to JSON string
    public function setHoursPeriodsAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['hours_periods'] = null;
        } else {
            $this->attributes['hours_periods'] = is_string($value) ? $value : json_encode($value);
        }
    }


}
