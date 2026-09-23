<?php

namespace App\Services\Student\Training\Reservation\params;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\ScheduleSetting;
use App\Repository\V2\Monitor\Schedule\Reservation\Parametrage\DestroyParamsReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Parametrage\StoreOrEditParamsReservationRepo;
use Carbon\Carbon;

class ReservationPramsService implements ReservationPramsInterface
{

    /**
     * @param array $attributes
     * @return ScheduleSetting
     */
    public function createOrUpdate(array $attributes): ScheduleSetting
    {
        $setting = StoreOrEditParamsReservationRepo::run($attributes);

        if ($setting->status == SituationStatusEnum::ACTIVE->value) {
            // Create new available reservation
            $this->handleActiveSetting($setting);
        } else {
            DestroyParamsReservationRepo::run($setting);
        }

        return $setting;
    }

    /**
     * Handle active settings by checking reservation and reserving it.
     *
     * @param $setting
     */
    protected function handleActiveSetting($setting): void
    {
        $this->tryReserveInIt($setting);
    }

    /**
     * Calculate the reservation for the setting.
     *
     * @param $setting
     * @return mixed
     */
    public function calcSettingOfResrvation($setting): mixed
    {
        $reservations = collect();
        DestroyParamsReservationRepo::run($setting);

        for ($i = 0; $i < $setting->number_weeks; $i++) {
            $this->processSettingDays($setting, $reservations, $i);
        }

        Reservation::query()->insert($reservations->toArray());
        return $reservations;
    }

    /**
     * Process the setting days and handle reservation checks.
     *
     * @param $setting
     * @param $reservations
     * @param $weekIndex
     */
    protected function processSettingDays($setting, $reservations, $weekIndex): void
    {
        foreach ($setting->days as $day => $hours) {
            foreach ($hours as $hour => $time) {
                $date = now()->addWeeks($weekIndex)->startOfWeek()->addDays($day - 1)->format('Y-m-d');
                $this->checkAndAddReservation($setting, $time, $date, $reservations);
            }
        }
    }

    /**
     * Check if a reservation is possible and add it to the reservation.
     *
     * @param $setting
     * @param $time
     * @param $date
     * @param $reservations
     */
    protected function checkAndAddReservation($setting, $time, $date, $reservations): void
    {
        $reservation = Reservation::query()->where('monitor_id', $setting->monitor_id)
            ->whereDate('date', $date)
            ->where(function ($query) use ($time) {
                $query->whereTime('start_at', '<=', $time['start_at'])
                    ->where(function ($query) use ($time) {
                        $query->whereTime('end_at', '>=', $time['end_at'])
                            ->orWhere(function ($query) use ($time) {
                                $query->whereTime('end_at', '>', $time['start_at'])
                                    ->whereTime('end_at', '<', $time['end_at']);
                            });
                    });
            })
            ->doesntExist();

        if ($reservation) {
            $reservations->push([
                'id' => fake()->uuid,
                'monitor_id' => $setting->monitor_id,
                'date' => $date,
                'hour' => Carbon::parse($time['start_at'])->diffInHours(Carbon::parse($time['end_at'])),
                'start_at' => $time['start_at'],
                'end_at' => $time['end_at'],
                'lieu_id' => $setting->lieu_id,
            ]);
        }
    }

    /**
     * Check reservation and reserve it if possible.
     *
     * @param $setting
     * @return mixed
     */
    public function tryReserveInIt($setting)
    {
        $reservations = collect();
        DestroyParamsReservationRepo::run($setting);

        foreach ($setting->days as $year => $months) {
            foreach ($months as $day => $hours) {
                foreach ($hours as $hour => $time) {
                    $date = Carbon::parse($year . '-' . $day)->format('Y-m-d');
                    $this->checkAndAddReservation($setting, $time, $date, $reservations);
                }
            }
        }

        Reservation::query()->insert($reservations->toArray());
        return $reservations;
    }
}
