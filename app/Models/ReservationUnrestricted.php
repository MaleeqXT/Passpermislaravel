<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Monitor\Schedule\Lieu;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class ReservationUnrestricted extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'reservations_unrestricted';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'start_at' => 'datetime:H:i',
        'end_at' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function lieu(): BelongsTo
    {
        return $this->belongsTo(Lieu::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(TrainingUnrestricted::class, 'reservation_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}