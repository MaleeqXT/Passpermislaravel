<?php

namespace App\Console\Commands;

use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Models\Roles\Student\Schedule\Cancellation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdatePassedCancellations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-passed-cancellations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update passed cancellations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get passed cancellations reservations
        $cancellations = Cancellation::query()
            ->where('status', CancellationStatusEnum::PENDING->value)
            ->where('is_justified', false)
            ->whereHas('training.reservation', function ($query) {
                return $query->whereDate('date', '<', now())
                    ->orWhere(function ($query) {
                        return $query->whereDate('date', now())->whereTime('end_at', '<=', now()->format('H:i'));
                    });
            })
            ->get();
        // update passed cancellations status
        if ($cancellations->isEmpty()) {
            Log::info('No passed cancellations to update.');
            return;
        }
        foreach ($cancellations as $cancellation) {
            $cancellation->update([
                'status' => CancellationStatusEnum::REFUSED->value,
                'comment' => "La demande d'annulation  a été refusée car la séance a déjà commencé ou est passée.",

            ]);
            Log::info('Found ' . $cancellations->count() . ' passed cancellations to update.');
        }
    }
}
