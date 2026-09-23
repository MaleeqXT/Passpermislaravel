<?php

namespace App\Models\Roles\Admin\Contact;

use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Admin\Contact\ContactUsFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    use HasFactory, HasUuids;

    protected static $unguarded = true;

    protected $table = 'contact_us';

    protected static function newFactory()
    {
        return ContactUsFactory::new();
    }
}
