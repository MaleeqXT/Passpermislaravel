<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationDelivery extends Model
{
    protected $fillable = ['message_id', 'push_notification_device_id', 'status', 'error_code', 'attempted_at', 'sent_at'];
    protected $casts = ['attempted_at' => 'datetime', 'sent_at' => 'datetime'];

    public function message(): BelongsTo { return $this->belongsTo(Message::class); }
    public function device(): BelongsTo { return $this->belongsTo(PushNotificationDevice::class, 'push_notification_device_id'); }
}
