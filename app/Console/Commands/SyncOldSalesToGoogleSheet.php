<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Roles\Admin\Offer\Order\Sale;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class SyncOldSalesToGoogleSheet extends Command
{
    protected $signature = 'sales:sync-google';
    protected $description = 'Sync old sales to Google Sheets';

    public function handle()
    {
        $spreadsheetId = '16FFocquR56XGYqIvrh71_6THGfPSkr9x61PGkSILbqs';
        $sheetName = 'Sheet1'; // ✅ Change if your real sheet name is different

        // ------------------------------
        // Google Client
        // ------------------------------
        $client = new Client();
        $client->setApplicationName('Laravel Google Sheets');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $service = new Sheets($client);

        // ------------------------------
        // Headers (A1)
        // ------------------------------
        $header = [[
            'Sale ID',
            'Student Name',
            'Student Email',
            'Reference',
            'Payment Status',
            'Payment Method',
            'Offer Name',
            'Amount',
            'Balance',
            'Date',
            'Print'
        ]];

        $service->spreadsheets_values->update(
            $spreadsheetId,
            "'{$sheetName}'!A1",
            new ValueRange(['values' => $header]),
            ['valueInputOption' => 'USER_ENTERED']
        );

        // ------------------------------
        // Fetch sales
        // ------------------------------
        $sales = Sale::with('student.user','cart.cartDetails.offer')->orderBy('id')->get();
        $rows = [];

        foreach ($sales as $sale) {
            $cartDetail = $sale->cart->cartDetails->first();
            $offerName = $cartDetail->offer->name ?? '';

            // ✅ PDF Print URL
            $printUrl = route('s.invoice', $sale->id);

            $rows[] = [
                $sale->id,
                $sale->student->user->name ?? '',
                $sale->student->user->email ?? '',
                $sale->reference,
                $sale->payment_status,
                $sale->payment_method,
                $offerName,
                $sale->amount,
                $sale->balance,
                $sale->created_at->toDateTimeString(),
                '=HYPERLINK("'.$printUrl.'","Print")'
            ];
        }

        // ------------------------------
        // Append data from A2
        // ------------------------------
        if (!empty($rows)) {
            $service->spreadsheets_values->update(
                $spreadsheetId,
                "'{$sheetName}'!A2",
                new ValueRange(['values' => $rows]),
                ['valueInputOption' => 'USER_ENTERED']
            );
        }

        $this->info(count($rows).' sales synced successfully 🚀');
    }
}
