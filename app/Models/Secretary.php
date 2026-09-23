<?php


namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Secretary extends Model
{
   use HasFactory, SoftDeletes  ,HasUuids;

    protected $fillable = [
        'user_id',
        'status',
        'neph',
        'date_of_code',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Secretary belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
