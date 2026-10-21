<?php

namespace App\Services\Monitor\Planning;

use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MonitorPlanningPdfService
{
    /** @param Collection<int, Reservation> $reservations */
    public function output(Monitor $monitor, Collection $reservations, ?CarbonInterface $from = null, ?CarbonInterface $to = null): string
    {
        return Pdf::loadView('pdf.monitor.planning', $this->viewData($monitor, $reservations, $from, $to))
            ->setPaper('a4', 'portrait')
            ->output();
    }

    public function filename(Monitor $monitor, ?CarbonInterface $from = null, ?CarbonInterface $to = null): string
    {
        $monitorName = Str::slug($monitor->user?->name ?: 'moniteur') ?: 'moniteur';

        return $from && $to
            ? sprintf('planning-%s-%s-au-%s.pdf', $monitorName, $from->toDateString(), $to->toDateString())
            : sprintf('planning-%s-complet.pdf', $monitorName);
    }

    /**
     * @param Collection<int, Reservation> $reservations
     * @return array{schoolName: string, monitorName: string, periodLabel: string, generatedAt: CarbonInterface, rows: Collection<int, array<string, string>>}
     */
    public function viewData(Monitor $monitor, Collection $reservations, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $timezone = config('app.timezone') ?: 'UTC';
        $schoolName = trim((string) config('app.name'));

        return [
            // Existing PDF templates use the same default while local setups can
            // still have Laravel configured as APP_NAME.
            'schoolName' => blank($schoolName) || strcasecmp($schoolName, 'Laravel') === 0
                ? 'PASSPERMISFACILE'
                : $schoolName,
            'monitorName' => $monitor->user?->name ?: 'Moniteur',
            'periodLabel' => $from && $to
                ? sprintf(
                    'Du %s au %s',
                    $from->copy()->locale('fr')->translatedFormat('d F Y'),
                    $to->copy()->locale('fr')->translatedFormat('d F Y'),
                )
                : 'Planning complet',
            'generatedAt' => now($timezone)->locale('fr'),
            'rows' => $reservations->values()->map(fn (Reservation $reservation) => $this->row($reservation)),
        ];
    }

    /** @return array<string, string> */
    private function row(Reservation $reservation): array
    {
        $training = $reservation->training;
        $student = $training?->student?->user;

        return [
            'date' => $reservation->date?->copy()->locale('fr')->translatedFormat('l d F Y') ?? 'Non renseignée',
            'start' => $reservation->start_at?->format('H:i') ?? '—',
            'end' => $reservation->end_at?->format('H:i') ?? '—',
            'student' => $student?->name ?: '—',
            'status' => $this->status($reservation),
            'location' => collect([$reservation->lieu?->zone?->name, $reservation->lieu?->name])
                ->filter()
                ->implode(' — ') ?: 'Non renseigné',
        ];
    }

    private function status(Reservation $reservation): string
    {
        $cancellationStatus = $reservation->training?->cancellation?->status;

        if (! $reservation->is_active || (int) $cancellationStatus === CancellationStatusEnum::SUCCESS_CANCELLED->value) {
            return 'Annulée';
        }

        if ((int) $cancellationStatus === CancellationStatusEnum::PENDING->value) {
            return 'Demande d’annulation';
        }

        return $reservation->training ? 'Leçon réservée' : 'Disponibilité';
    }
}
