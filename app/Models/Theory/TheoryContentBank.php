<?php

namespace App\Models\Theory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TheoryContentBank extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function questions(): HasMany
    {
        return $this->hasMany(TheoryQuestion::class, 'content_bank_id');
    }

    public function series(): HasMany
    {
        return $this->hasMany(TheorySeries::class, 'content_bank_id');
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(TheoryImportBatch::class, 'content_bank_id');
    }
}
