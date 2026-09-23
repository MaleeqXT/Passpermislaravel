<?php

namespace App\Console\Commands;

use App\Enums\V2\Admin\Popular\SituationStatusEnum;
use App\Models\Roles\Monitor\User\Monitor;
use App\Repository\V2\Monitor\Billing\StoreOrEditInvoiceRepo;
use App\Repository\V2\Monitor\Billing\SumTotalHourInvoiceRepo;
use Illuminate\Console\Command;

class GenerateFactureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-instructor-bill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Instructor Invoice:';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monitors = Monitor::query()
            ->whereHas('user', function ($query) {
                $query->where('status', SituationStatusEnum::ACTIVE);
            })
            ->with('details')
            ->get();

        $monitors->each(function ($monitor) {
            $invoiceData = $this->generateInvoiceData($monitor);

            StoreOrEditInvoiceRepo::run($monitor, $invoiceData);
        });
    }

    /**
     * Generate the invoice data for the monitor.
     *
     * @param Monitor $monitor
     * @return array
     */
    protected function generateInvoiceData(Monitor $monitor): array
    {
        $num_heures_f = $this->getFacturableHours($monitor);
        $prix_heure = $this->calculateHourlyRate($monitor);

        $num_heures_nf = $this->getNonFacturableHours($monitor);
        $total = $num_heures_f * $prix_heure;

        return [
            'monitor_id' => $monitor->id,
            'num_facture' => 'AD-' . fake()->unique()->randomNumber(8, true),
            'from' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
            'to' => now()->subMonth()->endOfMonth()->format('Y-m-d'),
            'montant' => $total,
            'details' => [
                'num_heures_f' => $num_heures_f,
                'num_heures_nf' => $num_heures_nf,
                'prix_heure' => $prix_heure,
                'total' => $total,
            ],
        ];
    }

    /**
     * Get the total facturable hours for the monitor within the last month.
     *
     * @param Monitor $monitor
     * @return float
     */
    protected function getFacturableHours(Monitor $monitor): float
    {
        return SumTotalHourInvoiceRepo::run($monitor, [
            'start' => now()->subMonth()->startOfMonth(),
            'end' => now()->subMonth()->endOfMonth(),
            'is_facturable' => true,
        ]);
    }

    /**
     * Get the total non-facturable hours for the monitor within the last month.
     *
     * @param Monitor $monitor
     * @return float
     */
    protected function getNonFacturableHours(Monitor $monitor): float
    {
        return SumTotalHourInvoiceRepo::run($monitor, [
            'start' => now()->subMonth()->startOfMonth(),
            'end' => now()->subMonth()->endOfMonth(),
            'is_not_facturable' => true,
        ]);
    }

    /**
     * Calculate the hourly rate for the monitor.
     *
     * @param Monitor $monitor
     * @return float
     */
    protected function calculateHourlyRate(Monitor $monitor): float
    {
        return $monitor->details->tarif_car + $monitor->details->tarif_enseignement;
    }
}
