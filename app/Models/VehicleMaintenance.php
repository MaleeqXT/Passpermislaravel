<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class VehicleMaintenance extends Model {
    use HasUuids;

    // The project migration intentionally uses the singular table name.
    protected $table = 'vehicle_maintenance';
    protected $guarded = [];

    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
}
