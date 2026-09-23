<?php

namespace App\Repository\V2\Monitor\Schedule\Reservation\Job\Admin;

use App\Models\TrainingUnrestricted;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchByMonthUnrestrictiveReservationRepo
{
    public static function run(array $attributes): array|Collection
    {
        return TrainingUnrestricted::query()
            ->with(['monitor.user:id,name,media', 'lieu.zone'])
            ->select('*', DB::raw('DAY(date) as day ,DATE(date) as datef'))
            ->when(isset($attributes['start']) && isset($attributes['end']), fn($query) => $query->whereBetween('date', [$attributes['start'], $attributes['end']]))
            ->when(isset($attributes['monitor_id']), fn($query) => $query->whereIn('monitor_id', $attributes['monitor_id']))
            ->when(isset($attributes['lieu_id']), fn($query) => $query->where('lieu_id', $attributes['lieu_id']))
            ->orderBy('monitor_id')
            ->get()
            ->groupBy(['datef']);
    }
}
