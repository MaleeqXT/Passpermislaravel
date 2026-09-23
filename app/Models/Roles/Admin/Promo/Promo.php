<?php

namespace App\Models\Roles\Admin\Promo;

use App\Enums\V2\Admin\Promo\PromoTypeEnum;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory, HasUuids;

    protected $with = ['offers'];
    protected static $unguarded = true;

    protected $casts = [
        'is_active' => 'boolean',
        'extra' => 'array'
    ];


    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'promo_offer');
    }

    public function extraProduct()
    {
        $end_at = Carbon::parse(data_get($this->extra, 'end_at'));
        $start_at = Carbon::parse(data_get($this->extra, 'start_at'));
        if (($start_at && $end_at && now()->between($start_at, $end_at))) {
            $offerIds = data_get($this->extra, 'offers', []);
            return Offer::query()->whereIn('id', $offerIds)->get();
        }
        return null;
    }
    public function extraProductAuto()
    {
        if ($this->type === PromoTypeEnum::CUSTOMIZE_HOME->value) {
            $offerIds = data_get($this->extra, 'offers_auto', []);
            return Offer::query()->whereIn('id', $offerIds)->get();
        }
        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
