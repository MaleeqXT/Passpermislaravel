<?php

namespace App\Repository\V2\Student\Schedule\Training\Offre;

use App\Models\Roles\Admin\Offer\Offer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FetchAllOffreRepo
{
    private const ZONE_AGENCIES = [
        '9eeb1c0b-3a2a-41f6-8887-e17879995335' => 'criel',
        '9ef0999c-3842-4de1-ade2-d88412135519' => 'toulouse',
    ];

    public static function run(?array $attributes = []): LengthAwarePaginator
    {
        $attributes ??= [];
        $searchTerm = '%' . request()->get('search') . '%';
        $student = auth()->user()?->student;
        $boiteType = $student?->boite_type ?? ($attributes['boite_type'] ?? null);
        $zoneIds = self::normaliseZoneIds($attributes['zone_id'] ?? null);

        $paginator = Offer::query()
            ->when($boiteType !== null, function (Builder $query) use ($boiteType) {
                if (in_array($boiteType, [0, 1, '0', '1'], true)) {
                    $query->where('is_auto', $boiteType);
                }
            })
            ->when($zoneIds !== [], function (Builder $query) use ($zoneIds) {
                self::applyZoneFilter($query, $zoneIds);
            })
            ->when(request()->get('search'), function (Builder $query) use ($searchTerm) {
                self::applySearchFilter($query, $searchTerm);
            })
            ->when(isset($attributes['status']), function (Builder $query) use ($attributes) {
                $query->where('status', $attributes['status']);
            })
            ->when(isset($attributes['is_cart']), function (Builder $query) use ($attributes) {
                self::applyIsCartFilter($query, $attributes);
            })
            ->when(isset($attributes['type']), function (Builder $query) use ($attributes) {
                self::applyTypeFilter($query, $attributes);
            })
            ->when(isset($attributes['type_offre']), function (Builder $query) use ($attributes) {
                self::applyTypeOffreFilter($query, $attributes);
            })
            ->when(isset($attributes['excluded_type_offre']), function (Builder $query) use ($attributes) {
                self::applyExcludedTypeOffreFilter($query, $attributes);
            })
            ->when(request()->get('p_min') && request()->get('p-max'), function (Builder $query) {
                self::applyPriceRangeFilter($query);
            })
            ->when(isset($attributes['balance']), function (Builder $query) use ($attributes) {
                self::applyBalanceFilter($query, $attributes);
            })
            ->when(isset($attributes['is_cpf']), function (Builder $query) use ($attributes) {
                self::applyIsCpfFilter($query, $attributes);
            })
            ->when(isset($attributes['is_auto']), function (Builder $query) use ($attributes) {
                self::applyIsAutoFilter($query, $attributes);
            })
            ->when(isset($attributes['excluded_ids']), function (Builder $query) use ($attributes) {
                self::applyExcludedIdsFilter($query, $attributes);
            })
            ->orderByDesc('order')
            ->paginate(min(max((int) ($attributes['per_page'] ?? 15), 1), 100));

        $paginator->getCollection()->transform(function (Offer $offer) use ($zoneIds) {
            return self::applyAgencyPricing($offer, $zoneIds);
        });

        return $paginator;
    }

    /**
     * Global offers (agency_pricing is NULL) are visible everywhere. Other offers
     * are visible once when any selected zone is in the JSON zone_id array.
     */
    public static function applyZoneFilter(Builder $query, array|string $zoneIds): void
    {
        $zoneIds = self::normaliseZoneIds($zoneIds);
        if ($zoneIds === []) {
            return;
        }

        $query->where(function (Builder $zoneQuery) use ($zoneIds) {
            $zoneQuery->whereNull('agency_pricing');

            foreach ($zoneIds as $zoneId) {
                // Support legacy UUID values as well as the new JSON zone array.
                $zoneQuery->orWhere('zone_id', $zoneId)
                    ->orWhereRaw(
                        'JSON_CONTAINS(IF(JSON_VALID(zone_id), zone_id, JSON_ARRAY()), JSON_QUOTE(?))',
                        [$zoneId]
                    );
            }
        });
    }

    private static function normaliseZoneIds(mixed $zoneIds): array
    {
        if (is_string($zoneIds)) {
            $decoded = json_decode($zoneIds, true);
            $zoneIds = json_last_error() === JSON_ERROR_NONE ? $decoded : [$zoneIds];
        }

        return array_values(array_unique(array_filter((array) $zoneIds, fn ($zoneId) => filled($zoneId))));
    }

    private static function applyAgencyPricing(Offer $offer, array $zoneIds): Offer
    {
        $pricing = self::normalisePricing($offer->agency_pricing);
        $pricingByAgency = collect($pricing)
            ->filter(fn ($item) => is_array($item) && filled($item['agency'] ?? null))
            ->keyBy(fn (array $item) => strtolower($item['agency']));

        $pricingByZone = [];
        foreach ($zoneIds as $zoneId) {
            $agency = self::ZONE_AGENCIES[$zoneId] ?? null;
            if ($agency && $pricingByAgency->has($agency)) {
                $pricingByZone[$zoneId] = $pricingByAgency->get($agency);
            }
        }

        $offer->setAttribute('agency_pricing_by_zone', $pricingByZone);

        // Normally one active zone is supplied. For it, expose the agency values
        // in the existing top-level fields so old React views need no duplicate data.
        $selectedPricing = count($zoneIds) === 1 ? ($pricingByZone[$zoneIds[0]] ?? null) : null;
        $offer->setAttribute('selected_agency_pricing', $selectedPricing);

        if ($selectedPricing) {
            foreach ([
                'price_ht', 'original_price', 'discounted_price', 'second_price',
                'balance', 'balance_2', 'multi_payment', 'total_payment',
                'final_price', 'caracteristiques',
            ] as $field) {
                if (array_key_exists($field, $selectedPricing)) {
                    $offer->setAttribute($field, $selectedPricing[$field]);
                }
            }

            if (array_key_exists('installments', $selectedPricing)) {
                $offer->setAttribute('installments_data', $selectedPricing['installments']);
            }
        }

        return $offer;
    }

    private static function normalisePricing(mixed $pricing): array
    {
        while (is_string($pricing)) {
            $decoded = json_decode($pricing, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            $pricing = $decoded;
        }

        if (!is_array($pricing)) {
            return [];
        }

        return array_is_list($pricing) ? $pricing : [$pricing];
    }

    private static function applySearchFilter(Builder $query, string $searchTerm): void
    {
        $query->where(function (Builder $query) use ($searchTerm) {
            $query->where('name', 'like', $searchTerm)
                ->orWhere('description', 'like', $searchTerm)
                ->orWhere('caracteristiques', 'like', $searchTerm)
                ->orWhere('price_ht', 'like', $searchTerm)
                ->orWhere('original_price', 'like', $searchTerm)
                ->orWhere('discounted_price', 'like', $searchTerm)
                ->orWhere('type_offre', 'like', $searchTerm);
        });
    }

    private static function applyIsCartFilter(Builder $query, array $attributes): void
    {
        $query->where('is_offer_cart', $attributes['is_cart']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private static function applyTypeFilter(Builder $query, array $attributes): void
    {
        $query->where('type', $attributes['type'] == 0 ? 1 : $attributes['type']);
    }

    private static function applyTypeOffreFilter(Builder $query, array $attributes): void
    {
        $query->where('type_offre', $attributes['type_offre']);
    }

    private static function applyExcludedTypeOffreFilter(Builder $query, array $attributes): void
    {
        $query->whereNotIn('type_offre', explode(',', $attributes['excluded_type_offre']));
    }

    private static function applyPriceRangeFilter(Builder $query): void
    {
        $query->whereBetween('final_price', [request()->get('p_min'), request()->get('p-max')]);
    }

    private static function applyBalanceFilter(Builder $query, array $attributes): void
    {
        $query->where('balance', $attributes['balance']);
    }

    private static function applyIsCpfFilter(Builder $query, array $attributes): void
    {
        $query->where('is_cpf', $attributes['is_cpf']);
    }

    private static function applyIsAutoFilter(Builder $query, array $attributes): void
    {
        $query->where('is_auto', $attributes['is_auto']);
    }

    private static function applyExcludedIdsFilter(Builder $query, array $attributes): void
    {
        $query->whereNotIn('id', $attributes['excluded_ids']);
    }
}
