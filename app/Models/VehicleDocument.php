<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class VehicleDocument extends Model {
    use HasUuids;
    protected $guarded = [];
    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): ?string { return $this->file_path ? url('/vehicle-files/'.$this->file_path) : null; }
    public function vehicle(): BelongsTo{return $this->belongsTo(Vehicle::class);}
}
