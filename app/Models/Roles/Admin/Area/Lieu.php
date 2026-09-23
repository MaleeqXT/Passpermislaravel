<?php

namespace App\Models\Roles\Admin\Area;

use App\Models\Roles\Monitor\User\Monitor;
use Database\Factories\Roles\Admin\Area\LieuFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lieu extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected $table = 'lieux';

    protected $casts = [
        'status' => 'boolean'
    ];

    protected static function newFactory()
    {
        return LieuFactory::new();
    }

    /**
     * @return BelongsTo
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * @return BelongsToMany
     */
    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class);
    }
}
