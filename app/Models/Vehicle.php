<?php
namespace App\Models;
use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Monitor\User\Monitor;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
class Vehicle extends Model {
    use HasUuids;
    protected $guarded = [];
    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? url('/vehicle-files/'.$this->photo) : null;
    }

    public function agency(): BelongsTo{return $this->belongsTo(Zone::class,'agency_id');}
    public function monitor(): BelongsTo{return $this->belongsTo(Monitor::class);}
    public function documents(): HasMany{return $this->hasMany(VehicleDocument::class);}
    public function maintenance(): HasOne{return $this->hasOne(VehicleMaintenance::class);}
}
