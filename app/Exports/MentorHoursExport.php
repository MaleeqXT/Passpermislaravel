<?php

namespace App\Exports;

use App\Models\Roles\Monitor\User\Monitor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class MentorHoursExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $monitor;

    public function __construct(Monitor $monitor)
    {
        $this->monitor = $monitor;
    }

    public function collection()
    {
        $reservations = $this->monitor->reservations()->get();

        $weeks = [];

        $dayMap = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche',
        ];

        foreach ($reservations as $reservation) {
            $date = Carbon::parse($reservation->date);
            $monthKey = $date->format('Y-m');
            $weekOfMonth = ceil($date->day / 7);

            // Week number within month
            $weekKey = $monthKey . '-W' . $weekOfMonth;

            // If this week row does not exist yet, initialize
            if (!isset($weeks[$weekKey])) {
                $weeks[$weekKey] = [
                    'sortKey' => $monthKey . '-' . str_pad($weekOfMonth, 2, '0', STR_PAD_LEFT),
                    'Month' => $monthKey,
                    'Week' => 'Semaine ' . $weekOfMonth,
                    'Date' => $date->format('Y-m-d'), // Add the date column
                    'Moniteur' => $this->monitor->user?->name ?? 'N/A',
                    'Lundi' => 0,
                    'Mardi' => 0,
                    'Mercredi' => 0,
                    'Jeudi' => 0,
                    'Vendredi' => 0,
                    'Samedi' => 0,
                    'Dimanche' => 0,
                    'Total' => 0,
                ];
            }

            $day = $date->format('l'); // e.g. Monday
            $frenchDay = $dayMap[$day] ?? $day;
            $hour = is_numeric($reservation->hour) ? $reservation->hour : 0; // Ensure hour is numeric
            $weeks[$weekKey][$frenchDay] += $hour; // Add validated hour value
        }

        // 🔹 After filling, calculate totals
        foreach ($weeks as &$week) {
            $week['Total'] =
                $week['Lundi'] +
                $week['Mardi'] +
                $week['Mercredi'] +
                $week['Jeudi'] +
                $week['Vendredi'] +
                $week['Samedi'] +
                $week['Dimanche'];
        }

        // Sort by month and week
        $weeksArray = array_values($weeks);
        usort($weeksArray, function($a, $b) {
            return strcmp($a['sortKey'], $b['sortKey']);
        });

        // Remove sortKey before returning
        foreach ($weeksArray as &$week) {
            unset($week['sortKey']);
        }

        return collect($weeksArray); // reset array keys
    }

    public function headings(): array
    {
        return [
            'Month',
            'Week',
            'Date', // Add the date column heading
            'Moniteur',
            'Lundi',
            'Mardi',
            'Mercredi',
            'Jeudi',
            'Vendredi',
            'Samedi',
            'Dimanche',
            'Total',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Bold headings
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Borders for all rows
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }
}
