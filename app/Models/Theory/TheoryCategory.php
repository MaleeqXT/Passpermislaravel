<?php

namespace App\Models\Theory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TheoryCategory extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function questions(): HasMany
    {
        return $this->hasMany(TheoryQuestion::class, 'category_id');
    }
}
