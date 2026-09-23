<?php

namespace App\Models\Roles\Student\User;

use App\Models\Roles\Admin\Offer\Cart\CartDetail;
use App\Models\Roles\Admin\Offer\Offer;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Student\User\WalletFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static $unguarded = true;

    protected static function newFactory()
    {
        return WalletFactory::new();
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    // get the cart items of the offer has many Through the offer
    public function cartDetails()
    {
        return $this->hasManyThrough(CartDetail::class, Offer::class, 'id', 'offer_id', 'offer_id', 'id');
    }
}
