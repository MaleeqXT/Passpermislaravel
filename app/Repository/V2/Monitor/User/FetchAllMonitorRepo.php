<?php

namespace App\Repository\V2\Monitor\User;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class FetchAllMonitorRepo
{
    /**
     * The monitor record belongs to a user, so school/zone filtering must be
     * performed against users.zone_id. Monitor locations are a separate filter.
     */
    public static function run(?array $attributes = [], array|string|null $zoneIds = null): LengthAwarePaginator
    {
        $attributes ??= [];
        $zoneIds = self::normaliseZoneIds($zoneIds);
        $searchTerms = preg_split('/\s+/', trim((string) request()->get('search', '')), -1, PREG_SPLIT_NO_EMPTY);
        $perPage = min(max((int) request()->get('per_page', 15), 1), 100);

        return User::query()
            ->when($searchTerms !== [], fn (Builder $query) => self::applySearchFilter($query, $searchTerms))
            ->isMonitor()
            ->when($zoneIds !== [], fn (Builder $query) => self::applyZoneFilter($query, $zoneIds))
            ->when(isset($attributes['lieu_id']), fn (Builder $query) => self::applyLieuFilter($query, $attributes['lieu_id']))
            ->with('monitor.details', 'monitor.lieux')
            ->when(
                array_key_exists('status', $attributes),
                fn (Builder $query) => self::applyStatusFilter($query, $attributes['status']),
                fn (Builder $query) => $query->whereHas('monitor', fn (Builder $monitorQuery) => $monitorQuery->where('status', SituationStatusEnum::ACTIVE->value))
            )
            ->orderBy('created_at', $attributes['sort'] ?? 'desc')
            ->paginate($perPage);
    }

    private static function normaliseZoneIds(array|string|null $zoneIds): array
    {
        return array_values(array_filter((array) $zoneIds, fn ($zoneId) => filled($zoneId)));
    }

    private static function applySearchFilter(Builder $query, array $searchTerms): Builder
    {
        foreach ($searchTerms as $term) {
            $like = '%' . $term . '%';
            $query->where(function (Builder $subQuery) use ($like) {
                $subQuery->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('adresse', 'like', $like)
                    ->orWhere('postal', 'like', $like)
                    ->orWhere('ville', 'like', $like);
            });
        }

        return $query;
    }

    private static function applyZoneFilter(Builder $query, array $zoneIds): Builder
    {
        return $query->where(function (Builder $zoneQuery) use ($zoneIds) {
            // New monitors are assigned directly through users.zone_id.
            $zoneQuery->whereIn('users.zone_id', $zoneIds)
                // Legacy monitors have no users.zone_id yet. Keep them visible
                // from their existing assigned location until their user record
                // is assigned a zone.
                ->orWhere(function (Builder $legacyQuery) use ($zoneIds) {
                    $legacyQuery->whereNull('users.zone_id')
                        ->whereHas('monitor.lieux', fn (Builder $locationQuery) => $locationQuery->whereIn('zone_id', $zoneIds));
                });
        });
    }

    private static function applyLieuFilter(Builder $query, string $lieuId): Builder
    {
        return $query->whereHas('monitor.lieux', fn (Builder $locationQuery) => $locationQuery->where('lieu_id', $lieuId));
    }

    private static function applyStatusFilter(Builder $query, mixed $status): Builder
    {
        return $status === 'all'
            ? $query
            : $query->whereHas('monitor', fn (Builder $monitorQuery) => $monitorQuery->where('status', $status));
    }
}
