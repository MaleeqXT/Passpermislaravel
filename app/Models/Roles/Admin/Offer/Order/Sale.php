<?php

namespace App\Models\Roles\Admin\Offer\Order;

use App\Models\Roles\Admin\Offer\Cart\Cart;
use App\Models\Roles\Student\User\Student;
// use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Admin\Offer\Order\SaleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static $unguarded = true;

    protected static function newFactory()
    {
        return SaleFactory::new();
    }

    /**
     * @return BelongsTo
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}
