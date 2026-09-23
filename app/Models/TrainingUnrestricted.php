<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Admin\Offer\Offer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
 
 
class TrainingUnrestricted extends Model
{
    use HasFactory, SoftDeletes, HasUuids;
 
    protected $table = 'trainings_unrestricted';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
 
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
 
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Roles\Monitor\Schedule\Reservation::class, 'reservation_id');
    }
 
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
 
     protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }
 
    public function reservationUnrestricted()
    {
        return $this->belongsTo(ReservationUnrestricted::class, 'reservation_id', 'id');
    }
}