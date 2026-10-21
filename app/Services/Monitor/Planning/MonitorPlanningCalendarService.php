<?php

namespace App\Services\Monitor\Planning;

use App\Enums\V2\Student\Schedule\Cancellation\CancellationStatusEnum;
use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Monitor\User\Monitor;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MonitorPlanningCalendarService
{
    public const TIMEZONE = 'Europe/Paris';

    /**
     * Return only driving lessons that belong to the supplied Moniteur.  The
     * withTrashed calls retain cancelled historical lessons in an iCal feed so
     * subscribers receive a STATUS:CANCELLED event instead of a stale booking.
     *
     * @return Collection<int, Reservation>
     */
    public function reservations(Monitor $monitor, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return Reservation::query()
            ->withTrashed()
            ->where('monitor_id', $monitor->id)
            ->whereHas('training', function (Builder $query): void {
                $query->withTrashed();
            })
            ->when($from && $to, fn (Builder $query) => $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]))
            ->with([
                'training' => function ($query): void {
                    $query->withTrashed()->with([
                        'student.user',
                        'cancellation',
                    ]);
                },
                'lieu.zone',
            ])
            ->orderBy('date')
            ->orderBy('start_at')
            ->get();
    }

    /** @param Collection<int, Reservation> $reservations */
    public function output(Monitor $monitor, Collection $reservations): string
    {
        $calendarName = 'Planning — '.($monitor->user?->name ?: 'Moniteur');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//PASSPERMISFACILE//Planning Moniteur//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            $this->property('X-WR-CALNAME', $calendarName),
            'X-WR-TIMEZONE:'.self::TIMEZONE,
            'BEGIN:VTIMEZONE',
            'TZID:'.self::TIMEZONE,
            'X-LIC-LOCATION:'.self::TIMEZONE,
            'BEGIN:DAYLIGHT',
            'TZOFFSETFROM:+0100',
            'TZOFFSETTO:+0200',
            'TZNAME:CEST',
            'DTSTART:19700329T020000',
            'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU',
            'END:DAYLIGHT',
            'BEGIN:STANDARD',
            'TZOFFSETFROM:+0200',
            'TZOFFSETTO:+0100',
            'TZNAME:CET',
            'DTSTART:19701025T030000',
            'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU',
            'END:STANDARD',
            'END:VTIMEZONE',
        ];

        foreach ($reservations as $reservation) {
            $lines = [...$lines, ...$this->eventLines($reservation)];
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    public function filename(Monitor $monitor, CarbonInterface $from, CarbonInterface $to): string
    {
        $monitorName = Str::slug($monitor->user?->name ?: 'moniteur') ?: 'moniteur';

        return sprintf('planning-%s-%s-au-%s.ics', $monitorName, $from->toDateString(), $to->toDateString());
    }

    /** @return list<string> */
    private function eventLines(Reservation $reservation): array
    {
        [$start, $end] = $this->eventDates($reservation);
        $cancelled = $this->isCancelled($reservation);
        $pendingCancellation = ! $cancelled && $this->isPendingCancellation($reservation);
        $studentName = trim((string) ($reservation->training?->student?->user?->name ?? ''));
        $location = collect([$reservation->lieu?->zone?->name, $reservation->lieu?->name])
            ->filter()
            ->implode(' — ');
        $statusLabel = $cancelled
            ? 'Annulée'
            : ($pendingCancellation ? 'Demande d’annulation' : 'Leçon réservée');
        $eventStatus = $cancelled ? 'CANCELLED' : ($pendingCancellation ? 'TENTATIVE' : 'CONFIRMED');
        $updatedAt = $reservation->updated_at?->copy()->utc() ?? now('UTC');
        $createdAt = $reservation->created_at?->copy()->utc() ?? $updatedAt;
        $summary = $cancelled
            ? 'Leçon annulée'
            : trim('Leçon de conduite'.($studentName !== '' ? ' — '.$studentName : ''));

        $description = ['Statut: '.$statusLabel];
        if (! $cancelled && $studentName !== '') {
            $description[] = 'Élève: '.$studentName;
        }
        if ($location !== '') {
            $description[] = 'Lieu: '.$location;
        }

        $lines = [
            'BEGIN:VEVENT',
            $this->property('UID', $this->uid($reservation)),
            'DTSTAMP:'.$createdAt->format('Ymd\\THis\\Z'),
            'LAST-MODIFIED:'.$updatedAt->format('Ymd\\THis\\Z'),
            'SEQUENCE:'.max(0, $updatedAt->getTimestamp()),
            'DTSTART;TZID='.self::TIMEZONE.':'.$start->format('Ymd\\THis'),
            'DTEND;TZID='.self::TIMEZONE.':'.$end->format('Ymd\\THis'),
            $this->property('SUMMARY', $summary),
            'STATUS:'.$eventStatus,
            $this->property('DESCRIPTION', implode("\n", $description)),
            'X-PASSPERMIS-STATUS:'.$this->escape($statusLabel),
        ];

        if ($location !== '') {
            $lines[] = $this->property('LOCATION', $location);
        }

        if ($cancelled) {
            $lines[] = 'TRANSP:TRANSPARENT';
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function eventDates(Reservation $reservation): array
    {
        $date = $reservation->date?->format('Y-m-d') ?? now(self::TIMEZONE)->toDateString();
        $startTime = $reservation->start_at?->format('H:i:s') ?? '00:00:00';
        $endTime = $reservation->end_at?->format('H:i:s') ?? '01:00:00';
        $start = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date.' '.$startTime, self::TIMEZONE);
        $end = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date.' '.$endTime, self::TIMEZONE);

        return [$start, $end->lessThanOrEqualTo($start) ? $start->addHour() : $end];
    }

    private function uid(Reservation $reservation): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'passpermisfacile.fr';

        return 'reservation-'.strtolower((string) $reservation->id).'@'.$host;
    }

    private function isCancelled(Reservation $reservation): bool
    {
        return $reservation->trashed()
            || ! $reservation->is_active
            || (int) $reservation->training?->cancellation?->status === CancellationStatusEnum::SUCCESS_CANCELLED->value;
    }

    private function isPendingCancellation(Reservation $reservation): bool
    {
        return (int) $reservation->training?->cancellation?->status === CancellationStatusEnum::PENDING->value;
    }

    private function property(string $name, string $value): string
    {
        return $this->fold($name.':'.$this->escape($value));
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'],
            $value,
        );
    }

    private function fold(string $line): string
    {
        $folded = '';

        while (strlen($line) > 75) {
            $chunk = function_exists('mb_strcut')
                ? mb_strcut($line, 0, 75, 'UTF-8')
                : substr($line, 0, 75);
            $folded .= $chunk."\r\n ";
            $line = substr($line, strlen($chunk));
        }

        return $folded.$line;
    }
}
