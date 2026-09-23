<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RdvPermisToken extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'scopes' => 'encrypted:array',
        'access_token_expires_at' => 'datetime',
        'refresh_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAccessTokenValid(): bool
    {
        return $this->status === 'connected'
            && filled($this->access_token)
            && ($this->access_token_expires_at === null || $this->access_token_expires_at->isFuture());
    }
}
