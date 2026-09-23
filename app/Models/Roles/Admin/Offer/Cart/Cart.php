<?php

namespace App\Models\Roles\Admin\Offer\Cart;

use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Student\User\Student;
use App\Scopes\Global\Cart\DisableCpfCartScoop;
use Database\Factories\Roles\Admin\Area\ZoneFactory;
use Database\Factories\Roles\Admin\Offer\Cart\CartFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([DisableCpfCartScoop::class])]
class Cart extends Model
{
    use HasFactory, HasUuids, SoftDeletes;


    protected static $unguarded = true;

    protected $with = ['cartDetails.offer'];

    protected static function newFactory()
    {
        return CartFactory::new();
    }

    /**
     * @return HasMany
     */
    public function cartDetails(): HasMany
    {
        return $this->hasMany(CartDetail::class);
    }

public function student()
{
    return $this->belongsTo(Student::class, 'student_id', 'id');
}

public function offers()
{
    return $this->belongsToMany(
        Offer::class,
        'cart_details',
        'cart_id',
        'offer_id'
    )->withPivot(['quantity', 'tranches', 'selected_price_type']);
}

    /**
     * @return BelongsTo
     */

    public function students(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }

    /**
     * Invoices / payments attached to this cart. Historically there was only one,
     * but we now split a sale record per offer when paying to keep amounts
     * separate. The `sale` helper continues to return the first entry for
     * backward compatibility.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
