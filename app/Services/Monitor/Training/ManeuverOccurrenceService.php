<?php

namespace App\Services\Monitor\Training;

use App\Models\ManeuverCatalog;
use App\Models\ManeuverOccurrence;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Student;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical manoeuvre history.  The legacy JSON counts remain in each lesson
 * for backwards compatibility, while this service records every occurrence
 * and its pedagogical rating.
 */
class ManeuverOccurrenceService
{
    public const DEFAULT_CATALOG = [
        ['slug' => 'epi_droit', 'name' => 'Stationnement en épi avant/arrière', 'group_name' => 'Stationnement', 'icon' => 'diagonal-right', 'sort_order' => 10],
        ['slug' => 'epi_gauche', 'name' => 'Stationnement en épi avant/arrière (sens inverse)', 'group_name' => 'Stationnement', 'icon' => 'diagonal-left', 'sort_order' => 20],
        ['slug' => 'bataille_droit', 'name' => 'Stationnement bataille avant/arrière', 'group_name' => 'Stationnement', 'icon' => 'parallel-right', 'sort_order' => 30],
        ['slug' => 'bataille_gauche', 'name' => 'Stationnement bataille avant/arrière (sens inverse)', 'group_name' => 'Stationnement', 'icon' => 'parallel-left', 'sort_order' => 40],
        ['slug' => 'creneau_droit', 'name' => 'Stationnement créneau', 'group_name' => 'Stationnement', 'icon' => 'bay-right', 'sort_order' => 50],
        ['slug' => 'creneau_gauche', 'name' => 'Stationnement créneau (sens inverse)', 'group_name' => 'Stationnement', 'icon' => 'bay-left', 'sort_order' => 60],
        ['slug' => 'marche_arriere', 'name' => 'Marche arrière en ligne droite', 'group_name' => 'Autres manœuvres', 'icon' => 'reverse', 'sort_order' => 70],
        ['slug' => 'arriere_courbe', 'name' => 'Marche arrière en courbe', 'group_name' => 'Autres manœuvres', 'icon' => 'reverse-curve', 'sort_order' => 80],
        ['slug' => 'demi_tour', 'name' => 'Demi-tour', 'group_name' => 'Autres manœuvres', 'icon' => 'u-turn', 'sort_order' => 90],
        ['slug' => 'demarrage_cote', 'name' => 'Démarrage en côte', 'group_name' => 'Autres manœuvres', 'icon' => 'hill', 'sort_order' => 100],
        ['slug' => 'giratoire', 'name' => 'Giratoire', 'group_name' => 'Autres manœuvres', 'icon' => 'roundabout', 'sort_order' => 110],
        ['slug' => 'arret_precision', 'name' => 'Arrêt de précision', 'group_name' => 'Autres manœuvres', 'icon' => 'precision-stop', 'sort_order' => 120],
    ];

    public function activeCatalog(): array
    {
        if (! Schema::hasTable('maneuver_catalogs')) {
            return array_map(fn (array $item) => $item + ['id' => $item['slug'], 'is_active' => true], self::DEFAULT_CATALOG);
        }

        // Tests and freshly provisioned databases can expose the table before
        // its seed data is available.  That differs from an administrator
        // deliberately deactivating every existing entry.
        if (! ManeuverCatalog::query()->exists()) {
            return array_map(fn (array $item) => $item + ['id' => $item['slug'], 'is_active' => true], self::DEFAULT_CATALOG);
        }

        $items = ManeuverCatalog::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (ManeuverCatalog $item) => $this->catalogPayload($item))->all();

        // Once the catalogue has been installed, an administrator may choose
        // to disable every entry.  In that case an empty result is intentional
        // and must not resurrect the default manoeuvres in the monitor UI.
        return $items;
    }

    public function allCatalog(): array
    {
        if (! Schema::hasTable('maneuver_catalogs')) return $this->activeCatalog();
        return ManeuverCatalog::query()->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (ManeuverCatalog $item) => $this->catalogPayload($item))->all();
    }

    public function ensureCatalog(): void
    {
        if (! Schema::hasTable('maneuver_catalogs')) return;
        foreach (self::DEFAULT_CATALOG as $item) {
            ManeuverCatalog::query()->firstOrCreate(['slug' => $item['slug']], $item + ['is_active' => true]);
        }
    }

    public function validateCounts(array $counts, array $previous = []): array
    {
        $normalized = collect($counts)->mapWithKeys(fn ($count, $slug) => [(string) $slug => max(0, min(9, (int) $count))])
            ->filter(fn ($count) => $count > 0)->all();
        $allowed = collect($this->activeCatalog())->pluck('slug')->flip();
        $historic = $this->allKnownSlugs();
        foreach ($normalized as $slug => $count) {
            if (! $historic->has($slug)) abort(422, 'Une manœuvre sélectionnée est invalide.');
            if (! $allowed->has($slug) && $count > (int) ($previous[$slug] ?? 0)) {
                abort(422, 'Cette manœuvre a été désactivée dans le catalogue.');
            }
        }
        return $normalized;
    }

    public function ratingsForReservation(Reservation $reservation, array $fallback = []): array
    {
        if (! Schema::hasTable('maneuver_occurrences')) return $this->normalizeRatings($fallback);
        $rows = ManeuverOccurrence::query()->where('reservation_id', $reservation->id)
            ->orderBy('maneuver_slug')->orderBy('sequence')->get();
        if ($rows->isEmpty()) return $this->normalizeRatings($fallback);
        return $rows->groupBy('maneuver_slug')->map(fn ($items) => $items->pluck('rating')->map(fn ($rating) => (int) $rating)->values()->all())->all();
    }

    public function sync(Reservation $reservation, Student $student, Monitor $monitor, array $counts, array $ratings = []): void
    {
        if (! Schema::hasTable('maneuver_occurrences')) return;
        $this->ensureCatalog();
        $catalog = ManeuverCatalog::query()->whereIn('slug', array_keys($counts))->get()->keyBy('slug');
        $normalizedRatings = $this->normalizeRatings($ratings);
        $slugs = array_keys($counts);
        ManeuverOccurrence::query()->where('reservation_id', $reservation->id)
            ->when($slugs, fn ($query) => $query->whereNotIn('maneuver_slug', $slugs))
            ->delete();
        foreach ($counts as $slug => $count) {
            $item = $catalog->get($slug);
            if (! $item) continue;
            $existing = ManeuverOccurrence::query()->where('reservation_id', $reservation->id)->where('maneuver_slug', $slug)->get()->keyBy('sequence');
            for ($sequence = 1; $sequence <= $count; $sequence++) {
                $rating = (int) ($normalizedRatings[$slug][$sequence - 1] ?? $existing->get($sequence)?->rating ?? 1);
                ManeuverOccurrence::query()->updateOrCreate(
                    ['reservation_id' => $reservation->id, 'maneuver_slug' => $slug, 'sequence' => $sequence],
                    ['maneuver_id' => $item->id, 'maneuver_name' => $item->name, 'student_id' => $student->id, 'monitor_id' => $monitor->id,
                        'occurred_on' => $reservation->date?->toDateString(), 'rating' => min(4, max(1, $rating))]
                );
            }
            ManeuverOccurrence::query()->where('reservation_id', $reservation->id)->where('maneuver_slug', $slug)->where('sequence', '>', $count)->delete();
        }
    }

    public function history(Monitor $monitor, Student $student): array
    {
        if (! Schema::hasTable('maneuver_occurrences')) return ['totals' => [], 'history' => []];
        $rows = ManeuverOccurrence::query()->where('monitor_id', $monitor->id)->where('student_id', $student->id)
            ->with('reservation')->orderByDesc('occurred_on')->orderByDesc('created_at')->orderByDesc('sequence')->get();
        return [
            'totals' => $rows->groupBy('maneuver_slug')->map(fn ($items) => $items->count())->all(),
            'history' => $rows->map(fn (ManeuverOccurrence $row) => [
                'id' => $row->id, 'maneuver_id' => $row->maneuver_slug, 'maneuver_name' => $row->maneuver_name,
                'date' => $row->occurred_on?->toDateString(), 'lesson_id' => $row->reservation_id,
                'rating' => $row->rating, 'sequence' => $row->sequence,
            ])->values()->all(),
        ];
    }

    private function allKnownSlugs()
    {
        if (! Schema::hasTable('maneuver_catalogs')) return collect(self::DEFAULT_CATALOG)->pluck('slug')->flip();
        $slugs = ManeuverCatalog::query()->pluck('slug')->flip();
        return $slugs->isEmpty() ? collect(self::DEFAULT_CATALOG)->pluck('slug')->flip() : $slugs;
    }

    private function catalogPayload(ManeuverCatalog $item): array
    {
        return ['id' => $item->id, 'slug' => $item->slug, 'name' => $item->name, 'group' => $item->group_name,
            'icon' => $item->icon, 'sort_order' => $item->sort_order, 'is_active' => $item->is_active];
    }

    private function normalizeRatings(array $ratings): array
    {
        $result = [];
        foreach ($ratings as $slug => $values) {
            $values = is_array($values) ? $values : [$values];
            $result[(string) $slug] = array_values(array_map(fn ($rating) => min(4, max(1, (int) $rating ?: 1)), $values));
        }
        return $result;
    }
}
