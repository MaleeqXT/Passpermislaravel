<?php

namespace App\Models\Roles\Admin\Area;

use App\Models\Roles\Student\User\Student;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected $casts = [
        'status' => 'boolean'
    ];

    protected static function newFactory()
    {
        return ZoneFactory::new();
    }

    /**
     * @return BelongsToMany
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    /**
     * @return HasMany
     */
    public function lieux(): HasMany
    {
        return $this->hasMany(Lieu::class);
    }

    /**
     * @return HasMany
     */
    public function zips(): HasMany
    {
        return $this->hasMany(Zip::class);
    }
}
