<?php

namespace App\Exports;

use App\Models\Roles\Student\User\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsSalesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        $rows = [];

        $students = Student::with('user', 'sales.cart.cartDetails.offer', 'wallets.offer')->get();

        foreach ($students as $student) {
            $walletBalance = $student->wallets()->sum('balance') ?? 0;

            $sales = $student->sales()->with('cart.cartDetails.offer')->get();

            if ($sales->isEmpty()) {
                // row for student with no sales
                $rows[] = [
                    $student->id,
                    $student->user?->name ?? '',
                    '',
                    '',
                    '',
                    0,
                    0,
                    0,
                    0,
                    $walletBalance,
                    '',
                    $student->created_at?->toDateString() ?? '',
                ];
                continue;
            }

            foreach ($sales as $sale) {
                $cart = $sale->cart;

                if (! $cart || $cart->cartDetails->isEmpty()) {
                    $rows[] = [
                        $student->id,
                        $student->user?->name ?? '',
                        $sale->reference ?? '',
                        '',
                        '',
                        0,
                        $sale->amount ?? 0,
                        0,
                        $sale->amount ?? 0,
                        $walletBalance,
                        $sale->payment_method ?? '',
                        $sale->created_at?->toDateString() ?? '',
                    ];
                    continue;
                }

                foreach ($cart->cartDetails as $detail) {
                    $offer = $detail->offer;
                    $rows[] = [
                        $student->id,
                        $student->user?->name ?? '',
                        $sale->reference ?? '',
                        $offer?->id ?? '',
                        $offer?->name ?? '',
                        $detail->quantity ?? 1,
                        $offer?->price_ht ?? 0,
                        $sale->amount ?? 0,
                        $sale->amount ?? 0,
                        $walletBalance,
                        $sale->payment_method ?? '',
                        $sale->created_at?->toDateString() ?? '',
                    ];
                }
            }
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'Student ID',
            'Student Name',
            'Sale Reference',
            'Offer ID',
            'Offer Name',
            'Quantity',
            'Offer Price HT',
            'Sale Amount',
            'Total Paid',
            'Wallet Balance',
            'Payment Method',
            'Sale Date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
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

        return [];
    }
}
