<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManeuverCatalog extends Model
{
    use HasUuids;

    protected $fillable = ['slug', 'name', 'group_name', 'icon', 'sort_order', 'is_active'];

    protected $casts = ['sort_order' => 'integer', 'is_active' => 'boolean'];

    public function occurrences(): HasMany
    {
        return $this->hasMany(ManeuverOccurrence::class, 'maneuver_id');
    }
}
