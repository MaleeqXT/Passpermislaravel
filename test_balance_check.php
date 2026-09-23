<?php
/**
 * Script to check if sales/wallets are being populated with balance from agency_pricing
 * Usage: php test_balance_check.php
 */

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Wallet;
use App\Models\Offer;

echo "=== Balance Extraction Verification ===\n\n";

// Check recent sales
echo "1. Checking recent SALES records:\n";
$recentSales = Sale::orderBy('created_at', 'desc')
    ->take(5)
    ->get(['id', 'reference', 'balance', 'offer_id', 'created_at']);

if ($recentSales->count() > 0) {
    foreach ($recentSales as $sale) {
        echo "   - Sale #{$sale->id} (ref: {$sale->reference}): balance={$sale->balance}\n";

        // Check if offer has agency_pricing
        if ($sale->offer_id) {
            $offer = Offer::find($sale->offer_id);
            if ($offer && $offer->agency_pricing) {
                echo "     └─ Offer {$sale->offer_id} HAS agency_pricing\n";
            } elseif ($offer) {
                echo "     └─ Offer {$sale->offer_id} root balance={$offer->balance}\n";
            }
        }
    }
} else {
    echo "   (No sales found)\n";
}

echo "\n2. Checking recent WALLETS records:\n";
$recentWallets = Wallet::orderBy('created_at', 'desc')
    ->take(5)
    ->get(['id', 'balance', 'offer_id', 'created_at']);

if ($recentWallets->count() > 0) {
    foreach ($recentWallets as $wallet) {
        echo "   - Wallet #{$wallet->id}: balance={$wallet->balance}\n";

        // Check if offer has agency_pricing
        if ($wallet->offer_id) {
            $offer = Offer::find($wallet->offer_id);
            if ($offer && $offer->agency_pricing) {
                echo "     └─ Offer {$wallet->offer_id} HAS agency_pricing\n";
            } elseif ($offer) {
                echo "     └─ Offer {$wallet->offer_id} root balance={$offer->balance}\n";
            }
        }
    }
} else {
    echo "   (No wallets found)\n";
}

echo "\n3. Checking OFFERS with agency_pricing:\n";
$offersWithAgency = Offer::whereNotNull('agency_pricing')->take(3)->get(['id', 'balance', 'agency_pricing']);

if ($offersWithAgency->count() > 0) {
    foreach ($offersWithAgency as $offer) {
        echo "   - Offer #{$offer->id}: root_balance={$offer->balance}\n";
        if ($offer->agency_pricing) {
            $pricing = is_string($offer->agency_pricing)
                ? json_decode($offer->agency_pricing, true)
                : $offer->agency_pricing;
            if (is_array($pricing)) {
                foreach ($pricing as $p) {
                    echo "     └─ {$p['agency']}: balance={$p['balance']}\n";
                }
            }
        }
    }
} else {
    echo "   (No offers with agency_pricing found)\n";
}

echo "\n=== Check Laravel logs: storage/logs/laravel.log ===\n";
echo "Look for messages starting with:\n";
echo "  - 'Sale Balance Extracted:'\n";
echo "  - 'Sale Balance NOT extracted:'\n";
echo "  - 'Extract Balance:'\n";
echo "  - 'Wallet Balance Extracted:'\n";

?>
