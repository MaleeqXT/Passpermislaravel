<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PushNotificationDevice extends Model
{
    protected $fillable = ['user_id', 'fcm_token', 'device_name', 'platform', 'browser', 'enabled', 'last_used_at'];
    protected $casts = ['enabled' => 'boolean', 'last_used_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function deliveries(): HasMany { return $this->hasMany(PushNotificationDelivery::class); }
}
