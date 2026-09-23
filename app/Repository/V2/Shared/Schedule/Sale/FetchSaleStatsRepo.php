<?php

namespace App\Repository\V2\Shared\Schedule\Sale;

use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Models\Roles\Admin\Offer\Order\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class FetchSaleStatsRepo
{
    /**
     * Fetch all advanced sales statistics based on request data.
     *
     * @param array $filters
     * @return array
     */
    public function run(array $filters): array
    {
        $start = Carbon::parse($filters['start'] ?? Carbon::now()->startOfMonth())->startOfDay();
        $end = Carbon::parse($filters['end'] ?? Carbon::now())->endOfDay();
        $zoneId = $filters['zone_id'] ?? null;

        $days = $start->diffInDays($end);
        $previousDate_1 = $start->copy()->subDays($days);
        $previousDate_2 = $end->copy()->subDays($days);

        $stats = $this->getSalesStatsQuery($start->toDateTimeString(), $end->toDateTimeString(), $zoneId)->first();
        $previousStats = $this->getSalesStatsQuery($previousDate_1->toDateTimeString(), $previousDate_2->toDateTimeString(), $zoneId)->first();
        // $weeklySales = $this->getWeeklySalesQuery()->get();

        return [
            'current' => (array) $stats,
            'comparison' => $this->calculateComparison($previousStats, $stats),
            // 'weekly' => $weeklySales,
        ];
    }

    /**
     * Get sales statistics query.
     */
    private function getSalesStatsQuery(string $start, string $end, ?string $zoneId = null)
    {
        return Sale::query()
            ->when($zoneId, fn (Builder $query) => $query->whereHas('student.user', fn ($subQuery) => $subQuery->where('zone_id', $zoneId)))
            ->selectRaw('
                COUNT(CASE WHEN payment_status = ? THEN 1 END) as pending_sales,
                COUNT(CASE WHEN payment_status = ? THEN 1 END) as paid_sales,
                COUNT(CASE WHEN payment_status = ? THEN 1 END) as refunded_sales,
                COUNT(CASE WHEN payment_status = ? THEN 1 END) as canceled_sales,
                SUM(CASE WHEN payment_status = ? THEN amount ELSE 0 END) as paid_revenue,
                SUM(CASE WHEN payment_status = ? THEN amount ELSE 0 END) as pending_revenue,
                SUM(CASE WHEN payment_status = ? THEN amount ELSE 0 END) as refunded_revenue,
                SUM(CASE WHEN payment_status = ? THEN amount ELSE 0 END) as canceled_revenue
            ')
            ->addBinding(SaleStatusEnum::PENDING->value)
            ->addBinding(SaleStatusEnum::PAID->value)
            ->addBinding(SaleStatusEnum::REFUNDED->value)
            ->addBinding(SaleStatusEnum::CANCELED->value)
            ->addBinding(SaleStatusEnum::PAID->value)
            ->addBinding(SaleStatusEnum::PENDING->value)
            ->addBinding(SaleStatusEnum::REFUNDED->value)
            ->addBinding(SaleStatusEnum::CANCELED->value)
            ->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Calculate percentage difference for revenue and sales.
     */
    private function calculateComparison($previousStats, $currentStats): array
    {
        return [
            'paid_revenue' => [
                'value' =>  (float) ($currentStats->paid_revenue ?? 0) - (float) ($previousStats->paid_revenue ?? 0),
                'perecent' =>  $this->calculatePercentage($previousStats->paid_revenue ?? 0, $currentStats->paid_revenue ?? 0),
            ],
            'pending_revenue' => [
                'value' =>  (float) ($currentStats->pending_revenue ?? 0) - (float) ($previousStats->pending_revenue ?? 0),
                'perecent' =>  $this->calculatePercentage($previousStats->pending_revenue ?? 0, $currentStats->pending_revenue ?? 0),
            ],
            'refunded_revenue' => [
                'value' =>  (float) ($currentStats->refunded_revenue ?? 0) - (float) ($previousStats->refunded_revenue ?? 0),
                'perecent' =>  $this->calculatePercentage($previousStats->refunded_revenue ?? 0, $currentStats->refunded_revenue ?? 0),
            ],
            'canceled_revenue' => [
                'value' =>  (float) ($currentStats->canceled_revenue ?? 0) - (float) ($previousStats->canceled_revenue ?? 0),
                'perecent' =>  $this->calculatePercentage($previousStats->canceled_revenue ?? 0, $currentStats->canceled_revenue ?? 0),
            ],
            'paid_sales' => [
                'value' =>  (int) ($currentStats->paid_sales ?? 0) - (int) ($previousStats->paid_sales ?? 0),
            ],
            'pending_sales' => [
                'value' =>  (int) ($currentStats->pending_sales ?? 0) - (int) ($previousStats->pending_sales ?? 0),
            ],
            'refunded_sales' => [
                'value' =>  (int) ($currentStats->refunded_sales ?? 0) - (int) ($previousStats->refunded_sales ?? 0),
            ],
            'canceled_sales' => [
                'value' =>  (int) ($currentStats->canceled_sales ?? 0) - (int) ($previousStats->canceled_sales ?? 0),
            ],
        ];
    }

    /**
     * Get the weekly sales query (Current Week).
     */
    private function getWeeklySalesQuery()
    {
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        return Sale::selectRaw('DATE(created_at) as date, SUM(amount) as total_sales')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupBy('date');
    }

    /**
     * Calculate percentage change.
     */
    private function calculatePercentage(float $previous, float $current): float
    {
        if ($previous == 0 && $current == 0) {
            return 0.0; // No change if both are zero
        }
        if ($previous == 0) {
            return 100.0; // If previous was 0 but current has value, consider 100% increase
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
