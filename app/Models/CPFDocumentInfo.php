<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CPFDocumentInfo extends Model
{
    use HasUuids;

    protected static $unguarded=true;


    protected $casts=[
        'test_pro'=>'array',
        'attestation_honneur'=>'array',
        'is_contact_formation'=>'boolean',
        'reservations'=>'array',
        'boite'=>'array',
    ];
}
