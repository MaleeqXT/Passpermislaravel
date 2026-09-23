<?php

namespace App\Observers;

use App\Models\Roles\Admin\Offer\Order\Sale;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class SaleObserver
{
    public function created(Sale $sale)
    {
        $spreadsheetId = '16FFocquR56XGYqIvrh71_6THGfPSkr9x61PGkSILbqs';
        $sheetName = 'accouts';

        $client = new Client();
        $client->setApplicationName('Laravel Google Sheets');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $service = new Sheets($client);

        $cartDetail = $sale->cart->cartDetails->first();
        $offerName = $cartDetail->offer->name ?? '';

        $row = [
            [
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
            ]
        ];

        $body = new ValueRange(['values' => $row]);
        $service->spreadsheets_values->append(
            $spreadsheetId,
            $sheetName.'!A2', // headers A1 me hain
            $body,
            ['valueInputOption' => 'RAW']
        );
    }
}
