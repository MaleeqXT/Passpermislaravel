<?php

namespace App\Exports;

use App\Models\Roles\Student\User\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActiveStudentsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    private $dateStart;
    private $dateEnd;
    private $search;

    public function __construct($dateStart = null, $dateEnd = null, $search = null)
    {
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
        $this->search = $search;
    }

    public function collection()
    {
        // follow the controller: only active users who have at least one training
        // record, and include the related reservation and paid sales.
        $studentsQuery = Student::with([
            'user',
            'trainings.reservation',
            'sales' => function ($q) {
                $q->where('payment_status', 'paid');
            },
        ])
        ->whereHas('user', function ($q) {
            $q->where('status', 1);
        })
        ->whereHas('trainings');

        // text search same as controller
        if ($this->search) {
            $studentsQuery->whereHas('user', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('name', 'like', "%{$this->search}%")
                       ->orWhere('email', 'like', "%{$this->search}%");
                });
            });
        }

        // Apply date filter if provided
        if ($this->dateStart || $this->dateEnd) {
            $studentsQuery->whereHas('wallets', function ($q) {
                if ($this->dateStart) {
                    $q->whereDate('created_at', '>=', $this->dateStart);
                }
                if ($this->dateEnd) {
                    $q->whereDate('created_at', '<=', $this->dateEnd);
                }
            });
        }

        $students = $studentsQuery->get();

        return $students->map(function($student) {
            // sum only completed/paid sales similar to controller logic
            $totalPaid = $student->sales->filter(function ($sale) {
                $raw = $sale->payment_status;
                if (is_numeric($raw) && (int) $raw === 2) {
                    return true;
                }
                $status = strtolower((string) ($raw ?? ''));
                return in_array($status, ['paid', 'success', 'succeeded', 'completed'], true);
            })->sum(function ($sale) {
                return (float) ($sale->amount ?? 0);
            });
            // list the reservation info from each training record.  status is not
            // present on trainings, so we just return ids for reference.
            $reservations = $student->trainings->map(function($training) {
                $date = $training->reservation->date ?? '';
                $start = $training->reservation->start_at ?? '';
                $end = $training->reservation->end_at ?? '';
                return trim("{$date} ({$start} - {$end}) [training: {$training->id}]");
            })->filter()->implode('; ');

            // also include sale IDs in a separate column for clarity
            $salesText = $student->sales->map(function($sale) {
                $raw = $sale->payment_status;
                $paid = false;
                if (is_numeric($raw) && (int) $raw === 2) {
                    $paid = true;
                } else {
                    $status = strtolower((string) ($raw ?? ''));
                    $paid = in_array($status, ['paid', 'success', 'succeeded', 'completed'], true);
                }
                return ($paid ? '#'.$sale->id.' '.$sale->amount : null);
            })->filter()->implode('; ');

            return [
                'ID' => $student->user->id,
                'Nom' => $student->user->name,
                'Email' => $student->user->email,
                'Téléphone' => $student->user->phone ?? 'N/A',
                'Ville' => $student->user->ville ?? 'N/A',
                'Status' => $student->user->status === 1 ? 'Actif' : 'Inactif',
                'Réservations' => $reservations,
                'Paiements (#/€)' => $salesText,
                'Total Payé (€)' => $totalPaid,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nom',
            'Email',
            'Téléphone',
            'Ville',
            'Status',
            'Réservations',
            'Total Payé (€)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
