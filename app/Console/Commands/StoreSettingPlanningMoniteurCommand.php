<?php

namespace App\Console\Commands;

use App\Repository\V2\Monitor\Schedule\Reservation\Parametrage\FetchAllParamsReservationRepo;
use App\Services\Student\Training\Reservation\params\ReservationPramsService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StoreSettingPlanningMoniteurCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setting-schedule-monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setting of the schedule of the  monitors in the database.';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle()
    {

        DB::beginTransaction();
        try {
            $this->processReservations();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Process all reservation settings and create new schedule for each.
     *
     * @return void
     */
    protected function processReservations()
    {
        FetchAllParamsReservationRepo::run()->each(function ($setting) {
            // Delete all existing schedule and create new ones
            (new ReservationPramsService)->tryReserveInIt($setting);
        });
    }
}
