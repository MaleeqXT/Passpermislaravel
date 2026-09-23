<?php

namespace App\Models\Roles\Admin\Offer;

use App\Models\Media\Media;
use App\Models\Roles\Admin\Offer\Cart\CartDetail;
use App\Models\Roles\Admin\Promo\Promo;
use App\Models\Roles\Student\User\Wallet;
use Database\Factories\Roles\Admin\Offer\OfferFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Roles\Student\User\Student;
class Offer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static $unguarded = true;
    protected $with = ['media'];

    protected $fillable = [
    'name',
    'is_cpf',
    'is_offer_cart',
    'description',
    'caracteristiques',
    'options',
    'price_ht',
    'original_price',
    'discounted_price',
    'second_price',      // ✅ NEW
    'final_price',
    'balance',
    'balance_2',        // ✅
    'is_auto',
    'multi_payment',
    'agency_name',       // ✅ NEW
    'type',
    'type_offre',
    'status',
    'order',
    'color',
    'is_evaluation',
];

    protected $casts = [
        'status' => 'boolean',
        'is_auto' => 'boolean',
        'is_offer_cart' => 'boolean',
        'zone_id' => 'array',
        'agency_pricing' => 'array',
        'installments_data' => 'array',
    ];

    protected static function newFactory()
    {
        return OfferFactory::new();
    }

    /**
     * @return MorphOne
     */
    public function media(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable');
    }

    public function student()
{
    return $this->belongsTo(Student::class, 'student_id', 'id');
}




    public function wallets()
    {
        return $this->hasOne(Wallet::class);
    }

    public function cartDetails()
    {
        return $this->hasMany(CartDetail::class);
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class, 'promo_offer');
    }
}
