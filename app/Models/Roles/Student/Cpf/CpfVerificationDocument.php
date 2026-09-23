<?php

namespace App\Models\Roles\Student\Cpf;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CpfVerificationDocument extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected $casts = [
        'data' => 'array'
    ];



    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function cpf(): BelongsTo
    {
        return $this->belongsTo(Cpf::class);
    }
}
