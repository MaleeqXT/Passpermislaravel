<?php

namespace App\Models\Media;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasFactory, HasUuids;

    protected $with = ['storageMedia'];

    protected static $unguarded= true;


    /**
     * Get the parent imageable model (user or post).
     */
    public function mediaable(): MorphTo
    {
        return $this->morphTo();
    }

    public function storageMedia()
    {
        return $this->belongsTo(StorageMedia::class);
    }
}
