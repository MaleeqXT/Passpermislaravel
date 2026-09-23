<?php

namespace App\Console\Commands;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Repository\V2\Monitor\Schedule\Reservation\Review\StoreReviewRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Review\FetchAllReservationInNotHaveReviewRepo;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class GenerateRatingMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-rating-monitor';


    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle()
    {

        $filter = [
            'lessDate' => now(),
            'lessEnd_at' => now()->format('H:i:s'),
        ];

        DB::beginTransaction();

        try {
            $this->processReservations($filter);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $this->handleError($e);
        }
    }

    /**
     * Process all reservations based on the provided filter.
     *
     * @param array $filter
     * @return void
     */
    protected function processReservations(array $filter)
    {
        FetchAllReservationInNotHaveReviewRepo::run($filter)->each(function ($reservation) {
            $data = $this->generateReviewData($reservation);
            StoreReviewRepo::run($reservation, $data);
        });
    }

    /**
     * Generate the review data for a reservation.
     *
     * @param $reservation
     * @return array
     */
    protected function generateReviewData($reservation): array
    {
        $betaPlanning = $this->getBetaPlanning($reservation);

        if ($betaPlanning && $betaPlanning->id == $reservation->id) {
            return ['is_estimated' => true];
        }

        return [];
    }

    /**
     * Get the beta schedules for a reservation.
     *
     * @param $reservation
     * @return mixed
     */
    protected function getBetaPlanning($reservation)
    {
        return Reservation::query()
            ->whereRelation('training', 'student_id', $reservation->training->student_id)
            ->orderBy('date')
            ->orderBy('start_at')
            ->first();
    }

    /**
     * Handle error during the process.
     *
     * @param Exception $e
     * @return void
     * @throws Exception
     */
    protected function handleError(Exception $e)
    {
        $this->error('Error Storing Instructor Rating: ' . $e->getMessage());
        throw $e;
    }
}
