<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentTrainingCancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'hours_requested',
        'status',
        'comment',
        'reservation_id', // store the related reservation when student requests a cancellation
    ];



       //  Add this relation
    public function student()
    {
        return $this->belongsTo(\App\Models\Roles\Student\User\Student::class, 'student_id');
    }

    /**
     * Reservation being cancelled when request is approved
     */
    public function reservation()
    {
        return $this->belongsTo(\App\Models\Roles\Monitor\Schedule\Reservation::class, 'reservation_id');
    }


}
