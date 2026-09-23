<?php

namespace App\Models\Roles\Monitor\User;

use App\Models\Roles\Admin\Area\Lieu;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Informations\Instructor\Car\Car;
use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorAccount;
use App\Models\Roles\Monitor\User\Informations\Instructor\InstructorDocument;
use App\Models\Roles\Monitor\User\Informations\MonitorInformation;
use App\Models\Roles\Student\Schedule\Rating;
use App\Models\Roles\Student\Schedule\ScheduleSetting;
use App\Models\User;
use Database\Factories\Roles\Monitor\User\MonitorFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ReservationRequest;
use Illuminate\Support\Str;
class Monitor extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static $unguarded = true;

    public $incrementing = false; // Disable auto-incrementing
    protected $keyType = 'string'; // Set ID type to string for UUID

    protected $fillable = [
        'id',
        'user_id',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    protected static function newFactory()
    {
        return MonitorFactory::new();
    }
    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function reservationRequests()
    {
        return $this->hasMany(ReservationRequest::class);
    }

    /**
     * @return HasOne
     */
    public function details(): HasOne
    {
        return $this->hasOne(MonitorInformation::class);
    }

    /**
     * @return BelongsToMany
     */
    public function lieux(): BelongsToMany
    {
        return $this->belongsToMany(Lieu::class);
    }

    /**
     * @return HasMany
     */
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    /**
     * @return HasMany
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * @return HasOne
     */
    public function account(): HasOne
    {
        return $this->hasOne(InstructorAccount::class);
    }

    public function scheduleSetting()
    {
        return $this->hasOne(ScheduleSetting::class);
    }


    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function instructorDocument()
    {
        return $this->hasOne(InstructorDocument::class);
    }


}
