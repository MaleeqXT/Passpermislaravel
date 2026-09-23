<?php

namespace App\Models;

use App\Models\Roles\Student\User\Student;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SatisfactionNotification extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = ['read_at' => 'datetime'];

    public function candidate()
    {
        return $this->belongsTo(Student::class, 'candidate_id');
    }

    public function response()
    {
        return $this->belongsTo(SatisfactionResponse::class, 'response_id');
    }

    public function offer()
    {
        return $this->belongsTo(\App\Models\Roles\Admin\Offer\Offer::class, 'offer_id');
    }
}
