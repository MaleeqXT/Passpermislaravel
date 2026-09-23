<?php

namespace App\Models\Roles\Student\User\Competency;

use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Student\User\Competency\MainCompetencyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MainCompetency extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;
    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function newFactory()
    {
        return MainCompetencyFactory::new();
    }

    /**
     * @return HasMany
     */
    public function competencies(): HasMany
    {
        return $this->hasMany(Competency::class);
    }
}
