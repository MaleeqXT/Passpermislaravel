<?php

namespace App\Models\Roles\Admin\Offer\Cart;

use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Student\User\Student;
use App\Scopes\Global\Cart\DisableCpfCartScoop;
use Database\Factories\Roles\Admin\Offer\Cart\CartDetailFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartDetail extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

        // 🔥 FIXED: Don't use $unguarded. Instead explicitly allow ONLY these fields.
        protected $fillable = [
            'cart_id',
            'offer_id',
            'offres_id',
            'quantity',
            'tranches',
            'selected_price_type',
            'selected_installment_no',
            'price',      // For menu offers: user-selected menu item price
            'balance',    // For menu offers: user-selected menu item balance/hours
        ];

        protected static function newFactory()
        {
            return CartDetailFactory::new();
        }

        /**
         * @return BelongsTo
         */
        public function cart(): BelongsTo
        {
            return $this->belongsTo(Cart::class);
        }

        /**
         * @return BelongsTo
         */
        public function offer(): BelongsTo
        {
            return $this->belongsTo(Offer::class);
        }

    public function scopeGetCart($query, Student $student, int $status = 2)
    {
        return $query->whereHas('cart', function ($query) use ($student, $status) {
            $query->withoutGlobalScope(DisableCpfCartScoop::class)->where('student_id', $student->id)
                ->whereIn('status', [CartStatusEnum::CPF->value, $status]);
        });
    }
}
