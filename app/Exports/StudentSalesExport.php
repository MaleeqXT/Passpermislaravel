<?php

namespace App\Exports;

use App\Models\Roles\Student\User\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class StudentSalesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $student;

    public function __construct(Student $student)
    {
        $this->student = $student;
    }

    public function collection()
    {
        $rows = [];

        $sales = $this->student->sales()->with('cart.cartDetails.offer')->get();

        foreach ($sales as $sale) {
            $cart = $sale->cart;
            if (! $cart) {
                // fallback row for sale without cart
                $rows[] = [
                    $this->student->id,
                    $this->student->user?->name ?? '',
                    $sale->reference ?? '',
                    '',
                    '',
                    $sale->amount ?? 0,
                    $sale->balance ?? '',
                    $sale->payment_method ?? '',
                    $sale->created_at?->toDateTimeString() ?? '',
                ];
                continue;
            }

            foreach ($cart->cartDetails as $detail) {
                $offer = $detail->offer;
                $rows[] = [
                    $this->student->id,
                    $this->student->user?->name ?? '',
                    $sale->reference ?? '',
                    $offer?->id ?? '',
                    $offer?->name ?? '',
                    $detail->quantity ?? 1,
                    $offer?->price_ht ?? 0,
                    $sale->amount ?? 0,
                    $sale->balance ?? '',
                    $sale->payment_method ?? '',
                    $sale->created_at?->toDateTimeString() ?? '',
                ];
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
            'Sale Balance',
            'Payment Method',
            'Sale Date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
        ]);

        return [];
    }
}
