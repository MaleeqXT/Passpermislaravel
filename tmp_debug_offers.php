<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Admin\Offer\Offer;

$student = Student::with('user')->first();
if (! $student) {
    echo "NO STUDENT\n";
    exit(1);
}

echo 'student ID: ' . $student->id . "\n";
echo 'student ville: ' . ($student->user->ville ?? '[null]') . "\n";

echo "offers:\n";
$offers = Offer::orderBy('name')->get();
foreach ($offers as $o) {
    echo sprintf("offer %s name=%s agency_name=%s\n", $o->id, $o->name, $o->agency_name ?? '[null]');
}

echo "\n=== filtered for student city '$student->user->ville' ===\n";
$studentCityRaw = $student->user->ville ?? '';
$studentCity = strtolower(trim($studentCityRaw));
$studentCityNormalized = preg_replace('/[^a-z0-9]/', '', $studentCity);

$targetAgencies = [];
if (str_contains($studentCityNormalized, 'creil') || str_contains($studentCityNormalized, 'criel')) {
    $targetAgencies = ['creil', 'criel'];
} elseif (str_contains($studentCityNormalized, 'toulouse')) {
    $targetAgencies = ['toulouse'];
}

$filteredOffers = $offers->filter(function ($offer) use ($cityType, $studentTransmission) {
    // 1. Transmission MUST match
    if ($studentTransmission === 'auto' && !$offer->is_auto) {
        return false;
    }

    if ($studentTransmission === 'manual' && $offer->is_auto) {
        return false;
    }

    // 2. City MUST match
    if (! $cityType) {
        return false;
    }

    $agencyRaw = $offer->agency_name ?? '';
    $agencyNormalized = preg_replace('/[^a-z0-9]/', '', strtolower(trim($agencyRaw)));

    // Match explicit city tag (includes Agency Creil, etc.)
    if (
        $agencyNormalized && (
            str_contains($agencyNormalized, $cityType) ||
            str_contains($cityType, $agencyNormalized) ||
            ($cityType === 'creil' && $agencyNormalized === 'criel')
        )
    ) {
        return true;
    }

    // Offers without any city restriction should be included for all cities.
    if (empty($agencyNormalized) && empty($offer->agency_pricing)) {
        return true;
    }

    // Check JSON agency_pricing
    $pricing = $offer->agency_pricing;
    if (is_string($pricing)) {
        $pricing = json_decode($pricing, true);
    }

    if (is_array($pricing)) {
        foreach ($pricing as $entry) {
            $entryAgency = strtolower(trim($entry['agency'] ?? ''));
            $entryAgencyNormalized = preg_replace('/[^a-z0-9]/', '', $entryAgency);

            if (
                $entryAgencyNormalized && (
                    str_contains($entryAgencyNormalized, $cityType) ||
                    str_contains($cityType, $entryAgencyNormalized) ||
                    ($cityType === 'creil' && $entryAgencyNormalized === 'criel')
                )
            ) {
                return true;
            }
        }
    }

    return false;
});

$filteredCount = $filteredOffers->count();
$filteredAuto = $filteredOffers->where('is_auto', true)->count();
$filteredManual = $filteredOffers->where('is_auto', false)->count();

echo "filtered offers count: {$filteredCount} (auto={$filteredAuto}, manual={$filteredManual})\n";
foreach ($filteredOffers as $o) {
    echo sprintf("  keep offer %s name=%s is_auto=%d agency_name=%s\n", $o->id, $o->name, $o->is_auto, $o->agency_name ?? '[null]');
}
