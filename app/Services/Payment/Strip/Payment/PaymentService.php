<?php

namespace App\Services\Payment\Strip\Payment;

use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Enums\V2\Student\Schedule\Wallet\WalletTypeEnum;
use App\Enums\V2\Student\Schedule\Wallet\WalletBalanceTypeEnum;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Shared\Base\Billing\Strip\ChargeStripeClientRepo;
use App\Repository\V2\Shared\Base\Billing\Strip\RefundStripeClientRepo;
use App\Repository\V2\Shared\Schedule\Sale\EditSaleRepo;
use App\Repository\V2\Shared\Schedule\Sale\StoreSaleRepo;
use App\Repository\V2\Student\Schedule\Sale\Admin\EditCartRepo;
use App\Repository\V2\Student\Schedule\Sale\FetchCartRepo;
use App\Repository\V2\Student\Schedule\Training\Offre\FetchOffreRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\FetchWalletRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\StoreOrEditWalletAction;
use App\Repository\V2\Student\Schedule\Training\Wallet\CalculateInstallmentBalanceAction;
use App\Repository\V2\Student\User\EditStudentRepo;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Support\Facades\Log;


class PaymentService
{

    /**
     * simple utility used when splitting a value over several tranches
     * and the customer pays one specific installment.
     */
    private static function splitInstallment(float $value, int $installmentNo, int $totalTranches): float
    {
        if ($totalTranches <= 1 || $installmentNo < 1) {
            return $value;
        }
        $base = floor($value / $totalTranches);
        $remainder = $value - ($base * $totalTranches);
        return $installmentNo === 1 ? $base + $remainder : $base;
    }

    /**
     * Determine amount and balance for an offer, taking into account
     * optional agency_pricing and/or installment splitting.  This mirrors
     * the logic used in the frontend (see `useCart.resolveOfferPrice`).
     *
     * @param \App\Models\Roles\Admin\Offer|null $offer
     * @param \App\Models\Roles\Student\Student|null $student
     * @param int|null $installmentNo  the single tranche number being paid
     * @return array{amount: float, balance: float, hasAgencyInstallments: bool}
     */
    private static function resolveOfferPricing($offer, $student = null, ?int $installmentNo = null, bool $skipFallback = false): array
    {
        // ensure numeric values are always floats so later comparison behaves correctly
        $amount  = (float) ($offer?->final_price ?? $offer?->original_price ?? 0);
        $balance = (float) ($offer?->balance ?? 0);
        $hasAgencyInstallments = false;

        // MENU-OFFER SPECIAL CASE
        if ($balance <= 0 && $offer) {
            $fieldsToCheck = [
                $offer->name ?? '',
                $offer->service_label ?? '',
                $offer->caracteristiques ?? '',
            ];
            foreach ($fieldsToCheck as $fieldText) {
                if (!empty($fieldText) && is_string($fieldText) && preg_match('/(\d+(?:[\.,]\d*)?)(\s*h)/i', $fieldText, $m)) {
                    $num = str_replace(',', '.', $m[1]);
                    $parsed = (float) $num;
                    if ($parsed > 0) {
                        $balance = round($parsed, 2);
                        Log::info('Menu balance inferred', ['balance' => $balance, 'offer' => $offer->id]);
                        break;
                    }
                }
            }
        }

        // Agency pricing handling
        $matchedAgency = null;
        $matchedPricingData = null;

        if ($offer && $offer->agency_pricing) {
            $pricingList = is_string($offer->agency_pricing) ? json_decode($offer->agency_pricing, true) : $offer->agency_pricing;
            if (is_array($pricingList) && count($pricingList)) {
                $agency = '';
                $studentVille = '';
                if ($student) {
                    // ville is in the user table, accessed via student->user relationship
                    $studentVille = strtolower(trim($student->user?->ville ?? $student->ville ?? ''));
                    if (str_contains($studentVille, 'toulouse')) {
                        $agency = 'toulouse';
                    } elseif (str_contains($studentVille, 'creil')) {
                        $agency = 'creil';
                    }
                }

                Log::info('Agency matching attempt', [
                    'student_ville' => $studentVille,
                    'detected_agency' => $agency,
                    'offer_id' => $offer->id,
                    'available_agencies' => array_column($pricingList, 'agency')
                ]);

                // Find best match
                $pricing = null;
                $agencyNormalized = strtolower(preg_replace('/[^a-z0-9]/', '', $agency ?? ''));
                $studentVilleNormalized = strtolower(preg_replace('/[^a-z0-9]/', '', $studentVille ?? ''));
                foreach ($pricingList as $p) {
                    $pAgencyRaw = $p['agency'] ?? '';
                    $pAgency = strtolower(trim($pAgencyRaw));
                    $pAgencyNormalized = strtolower(preg_replace('/[^a-z0-9]/', '', $pAgency));
                    if ($agency && ($pAgency === $agency || $pAgencyNormalized === $agencyNormalized)) {
                        $pricing = $p; break;
                    }
                    if ($agency && $agencyNormalized && $pAgencyNormalized && levenshtein($pAgencyNormalized, $agencyNormalized) <= 1) {
                        $pricing = $p; break;
                    }
                    if ($studentVille && str_contains($pAgency, $studentVille)) { $pricing = $p; break; }
                    if ($studentVilleNormalized && $pAgencyNormalized && levenshtein($pAgencyNormalized, $studentVilleNormalized) <= 1) { $pricing = $p; break; }
                }
                if (!$pricing && $agency) {
                    foreach ($pricingList as $p) {
                        $pAgency = strtolower(trim($p['agency'] ?? ''));
                        if (str_contains($pAgency, $agency) || str_contains($agency, $pAgency)) { $pricing = $p; break; }
                    }
                }
                if (!$pricing) { $pricing = $pricingList[0]; }

                // Extract amounts
                if (!empty($pricing['installments']) && is_array($pricing['installments']) && $installmentNo) {
                    $totalTranches = count($pricing['installments']);
                    foreach ($pricing['installments'] as $inst) {
                        if (isset($inst['no']) && $inst['no'] == $installmentNo && isset($inst['amount'])) {
                            $amount = (float) $inst['amount'];
                            if ($balance > 0 && $totalTranches > 0) {
                                $baseBalance = floor($balance / $totalTranches);
                                $remainderBalance = $balance - ($baseBalance * $totalTranches);
                                $balance = $installmentNo === 1 ? $baseBalance + $remainderBalance : $baseBalance;
                            }
                            $hasAgencyInstallments = true;
                            break;
                        }
                    }
                } else {
                    if (isset($pricing['price_ht'])) { $amount = (float) $pricing['price_ht']; }
                    elseif (isset($pricing['total_payment'])) { $amount = (float) $pricing['total_payment']; }
                    elseif (isset($pricing['original_price'])) { $amount = (float) $pricing['original_price']; }
                    elseif (isset($pricing['amount'])) { $amount = (float) $pricing['amount']; }
                    if (isset($pricing['balance'])) { $balance = (float) $pricing['balance']; }
                }

                $matchedAgency = $pricing['agency'] ?? null;
                $matchedPricingData = $pricing ?? null;

                Log::info('💰 FINAL AMOUNT EXTRACTED FROM AGENCY PRICING', [
                    'offer_id' => $offer?->id,
                    'agency' => $matchedAgency ?? 'UNKNOWN',
                    'extracted_amount' => $amount,
                    'extracted_balance' => $balance,
                    'pricing_data' => $matchedPricingData,
                    'student_ville' => $studentVille ?? 'N/A'
                ]);
            }
        }

        // Fallback: for "menu card" offers the agency pricing may not include
        // a direct numeric price but the `caracteristiques` HTML/text often
        // contains component prices. When amount is zero, try to extract
        // euro values from `caracteristiques` from either the offer model or
        // the matched agency pricing data and use their sum as the amount.
        // Skip fallback for multi-item menus to prevent summing ALL caracteristiques
        if ($skipFallback) {
            Log::info('Menu offer (multi-item): skipping fallback', ['offer_id' => $offer?->id]);
            return [
                'amount' => (float) $amount,
                'balance' => (float) $balance,
                'hasAgencyInstallments' => $hasAgencyInstallments,
                'matchedAgency' => null,
                'pricingData' => null,
            ];
        }
        Log::info('fallback entry check', [
            // log amount with type info so we can see if it was int vs float
            'amount' => $amount,
            'amount_type' => gettype($amount),
            'offerCarac' => isset($offer?->caracteristiques) ? substr($offer->caracteristiques,0,50) : null,
            'matchedCarac' => isset($matchedPricingData['caracteristiques']) ? substr($matchedPricingData['caracteristiques'],0,50) : null,
        ]);
        // run fallback whenever we don't have a positive numeric amount; using loose
        // comparison avoids issues where $amount is the integer 0 which would not
        // satisfy a strict === 0.0 check.
        if (empty($amount)) {
            $caracSource = null;
            if ($offer && !empty($offer->caracteristiques) && is_string($offer->caracteristiques)) {
                $caracSource = $offer->caracteristiques;
            } elseif (!empty($matchedPricingData['caracteristiques']) && is_string($matchedPricingData['caracteristiques'])) {
                $caracSource = $matchedPricingData['caracteristiques'];
            }

            if ($caracSource) {
                try {
                    // extract patterns like '20€' or '20 €' or '20.50€'
                    // match numbers that precede a euro symbol, allowing for HTML
                    // entities and non-breaking spaces by using a lazy match up
                    // to the euro symbol. This is more robust for HTML content.
                    if (preg_match_all('/(\d+[\.,]?\d*)\s*(?:&nbsp;|\s)*€/u', $caracSource, $m)) {
                        $values = $m[1] ?? [];
                        $nums = [];
                        foreach ($values as $v) {
                            $nums[] = (float) str_replace(',', '.', $v);
                        }
                        if (count($nums) > 0) {
                            // If multiple euro amounts are present in caracteristiques
                            // prefer the largest single item price (more likely the
                            // menu's main price) instead of summing all components.
                            // Fall back to sum only if a single value exists.
                            if (count($nums) === 1) {
                                $amount = round($nums[0], 2);
                            } else {
                                $amount = round(max($nums), 2);
                            }
                            Log::info('Menu price fallback used: extracted euro amounts from caracteristiques', ['offer_id' => $offer?->id ?? null, 'values' => $nums, 'fallback_amount' => $amount]);
                        }
                    } else {
                        Log::info('Menu price fallback attempted but no euro amounts found in caracteristiques', ['offer_id' => $offer?->id ?? null]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to parse caracteristiques for menu price fallback', ['offer_id' => $offer?->id ?? null, 'error' => $e->getMessage()]);
                }
            }
        }

        return [
            'amount' => (float) $amount,
            'balance' => (float) $balance,
            'hasAgencyInstallments' => $hasAgencyInstallments,
            'matchedAgency' => $matchedAgency ?? null,
            'pricingData' => $matchedPricingData ?? null,
        ];
    }

    /**
     * @param ChargeStripeClientRepo $createCharge
     * @param RefundStripeClientRepo $createRefund
     */
    public function __construct(public ChargeStripeClientRepo $createCharge, public RefundStripeClientRepo $createRefund) {}


    /**
     * @param array $attributes
     * @return \Illuminate\Http\JsonResponse|bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function action(array $attributes, ?Student $student = null)
    {
        DB::beginTransaction();
        try {
            $cart = FetchCartRepo::run($student?->user);
            if (!$cart) {
                DB::rollback();
                Log::error('Payment processing failed: Cart not found for user');
                return response()->json(['error' => 'Cart not found. Please add items to cart before paying.'], 400);
            }

            // build one sale per unique offer, grouping duplicate cartDetails by offer_id
            $sales = [];
            $groupedByOfferId = [];  // Group cartDetails by offer_id to merge duplicates
            $installmentPayload = Arr::get($attributes, 'installments', []);

            Log::info('🔵 PaymentService::action() START', [
                'totalDetails' => $cart->cartDetails->count(),
                'hasInstallments' => count($installmentPayload) > 0,
                'student_id' => $cart->student?->id,
                'student_ville' => $cart->student?->ville,
                'student_email' => $cart->student?->email,
            ]);

            // Group cartDetails by offer_id to handle duplicates (same offer added multiple times)
            // For menu offers, keep ALL cartDetails so we can sum their individual prices
            foreach ($cart->cartDetails as $cartDetail) {
                $offerId = $cartDetail->offer?->id;
                if (!$offerId) continue;

                if (!isset($groupedByOfferId[$offerId])) {
                    $groupedByOfferId[$offerId] = [
                        'cartDetail' => $cartDetail,  // use first instance for reference
                        'cartDetails' => [],  // keep all cartDetails for menu items
                        'quantity' => 0,
                        'tranches' => $cartDetail->tranches ?? 0,
                    ];
                }
                $groupedByOfferId[$offerId]['cartDetails'][] = $cartDetail;
                $groupedByOfferId[$offerId]['quantity'] += ($cartDetail->quantity ?? 1);
            }

            Log::info('Grouped cartDetails by offer', [
                'uniqueOffers' => count($groupedByOfferId),
                'groupedOffers' => array_keys($groupedByOfferId),
            ]);

            foreach ($groupedByOfferId as $offerId => $groupData) {
                $cartDetail = $groupData['cartDetail'];
                $totalQuantity = $groupData['quantity'];
                $allCartDetails = $groupData['cartDetails'] ?? [$cartDetail];

                Log::info('CartDetail debug', [
                    'offer_id' => $cartDetail->offer?->id,
                    'offer_name' => $cartDetail->offer?->name,
                    'cartDetail_id' => $cartDetail->id,
                    'cartDetail_name' => $cartDetail->name ?? $cartDetail->service_label,
                    'cartDetail_price' => $cartDetail->price,
                    'cartDetail_balance' => $cartDetail->balance,
                    'total_cart_details_for_offer' => count($allCartDetails),
                ]);

                // CRITICAL: Refresh cart details from DB to ensure price/balance fields are loaded
                // (in case they were added to model after initial fetch)
                $allCartDetailsRefreshed = [];
                foreach ($allCartDetails as $cd) {
                    $refreshed = \App\Models\Roles\Admin\Offer\Cart\CartDetail::find($cd->id);
                    if ($refreshed) {
                        $allCartDetailsRefreshed[] = $refreshed;
                    } else {
                        $allCartDetailsRefreshed[] = $cd;  // Fallback to original if not found
                    }
                }
                $allCartDetails = $allCartDetailsRefreshed;

                Log::info('CartDetails refreshed from DB', [
                    'offer_id' => $cartDetail->offer?->id,
                    'refreshed_count' => count($allCartDetails),
                ]);

                $offer = $cartDetail->offer;

                // Detect menu offer: either multiple cartDetails OR offer has no direct price but has caracteristiques
                $hasDirectPrice = !empty($offer?->final_price) || !empty($offer?->original_price)
                    || !empty($offer?->price_ht);
                $hasCaracteristiques = !empty($offer?->caracteristiques);
                $isMenuOffer = (count($allCartDetails) > 1) || (!$hasDirectPrice && $hasCaracteristiques);

                Log::info('Menu offer detection', [
                    'offer_id' => $offer?->id,
                    'offer_name' => $offer?->name,
                    'cart_detail_count' => count($allCartDetails),
                    'hasDirectPrice' => $hasDirectPrice,
                    'hasCaracteristiques' => $hasCaracteristiques,
                    'isMenuOffer' => $isMenuOffer,
                    'offer_final_price' => $offer?->final_price,
                    'offer_original_price' => $offer?->original_price,
                    'offer_price_ht' => $offer?->price_ht,
                    'offer_agency_pricing' => isset($offer?->agency_pricing) ? 'SET' : 'NULL',
                    'caracteristiques_length' => strlen($offer?->caracteristiques ?? ''),
                    'caracteristiques_preview' => substr($offer?->caracteristiques ?? '', 0, 100),
                ]);

                $tranches = $cartDetail->tranches ?? 0;

                // CRITICAL: First check installmentPayload (frontend's selected installment),
                // then fall back to DB. Frontend payload is the source of truth during payment.
                $matching = Arr::first($installmentPayload, fn($i) => isset($i['offer_id']) && $i['offer_id'] == ($offer?->id));
                $selectedInstallment = $matching['installment_no'] ?? $cartDetail->selected_installment_no ?? null;

                // resolve the pricing using helper (handles agency_pricing, student agency,
                // and optional installment splitting when an installment number is known).
                // For menu offers: skip fallback to avoid summing all caracteristiques
                $pricingResult = self::resolveOfferPricing($offer, $cart->student, $selectedInstallment, $isMenuOffer);
                $detailAmount  = $pricingResult['amount'];
                $detailBalance = $pricingResult['balance'];
                $hasAgencyInstallments = $pricingResult['hasAgencyInstallments'] ?? false;

                Log::info('Resolved offer pricing result', [
                    'offer_id' => $offer?->id,
                    'matchedAgency' => $pricingResult['matchedAgency'] ?? null,
                    'pricingData' => $pricingResult['pricingData'] ?? null,
                    'detailAmount' => $detailAmount,
                    'detailBalance' => $detailBalance,
                    'selectedInstallment' => $selectedInstallment,
                ]);

                // if frontend stored an explicit price on cartDetail use it
                if (!$isMenuOffer && isset($cartDetail->price) && $cartDetail->price > 0) {
                    Log::info('Overriding resolved amount with cartDetail price', [
                        'offer_id' => $offer?->id,
                        'cartDetail_price' => $cartDetail->price,
                    ]);
                    $detailAmount = (float) $cartDetail->price;
                    if (isset($cartDetail->balance)) {
                        $detailBalance = (float) $cartDetail->balance;
                    }
                }

                Log::info('🟡 PaymentService detail pricing resolved', [
                    'offer_id' => $offer?->id,
                    'offer_name' => $offer?->name,
                    'db_balance' => $offer?->balance,
                    'computed_balance' => $detailBalance,
                    'tranches' => $tranches,
                    'totalQuantity' => $totalQuantity,
                    'selectedInstallment' => $selectedInstallment,
                ]);

                // For menu offers: prefer summing actual cartDetail prices when
                // multiple items are present. This ensures the user's selected
                // menu items determine the sale amount even if agency_pricing
                // provides a generic 'caracteristiques' summary.
                if ($isMenuOffer) {

    Log::info('🎯 ENTERING MENU OFFER PROCESSING', [
        'offer_id' => $offer?->id,
        'offer_name' => $offer?->name,
        'allCartDetails_count' => count($allCartDetails),
    ]);

    $menuItemsTotal = 0.0;
    $menuBalanceTotal = 0.0;  // Track total balance/hours
    $matchedLines = [];  // Track matched lines for balance extraction

    // DEBUG: Log what prices are in cartDetails from database
    $cartDetailDebug = [];
    foreach ($allCartDetails as $cd) {
        $cartDetailDebug[] = [
            'id' => $cd->id,
            'name' => $cd->name,
            'price' => $cd->price,
            'balance' => $cd->balance,
            'final_price' => $cd->final_price,
            'quantity' => $cd->quantity,
            'price_is_null' => is_null($cd->price),
            'price_type' => gettype($cd->price),
        ];
    }
    Log::info('📊 CartDetails from DB - BEFORE processing', [
        'offer_id' => $offer?->id,
        'details' => $cartDetailDebug,
    ]);

    // STEP 1: Use explicit cartDetail price first
    // Read BOTH price and final_price fields
    foreach ($allCartDetails as $menuItem) {
        // Try price field first, then final_price, then quantity * price combo
        $itemPrice = 0;

        // Check all possible price fields
        if (!empty($menuItem->price) && $menuItem->price > 0) {
            $itemPrice = (float) $menuItem->price;
            Log::info('✅ Found price in cartDetail.price', [
                'item_id' => $menuItem->id,
                'price' => $itemPrice,
            ]);
        } elseif (!empty($menuItem->final_price) && $menuItem->final_price > 0) {
            $itemPrice = (float) $menuItem->final_price;
            Log::info('✅ Found price in cartDetail.final_price', [
                'item_id' => $menuItem->id,
                'price' => $itemPrice,
            ]);
        }

        $qty = (int) ($menuItem->quantity ?? 1);
        $menuItemsTotal += ($itemPrice * $qty);

        // Also collect balance if stored (for menu items where user selected specific option)
        $itemBalance = 0;
        if (!empty($menuItem->balance) && $menuItem->balance > 0) {
            $itemBalance = (float) $menuItem->balance;
        }
        $menuBalanceTotal += ($itemBalance * $qty);

        Log::info('STEP1: Processing item', [
            'item_id' => $menuItem->id,
            'itemPrice' => $itemPrice,
            'qty' => $qty,
            'itemPrice_x_qty' => ($itemPrice * $qty),
            'itemBalance' => $itemBalance,
            'running_menuItemsTotal' => $menuItemsTotal,
        ]);
    }

    Log::info('STEP1: After explicit cartDetail prices', [
        'offer_id' => $offer?->id,
        'menuItemsTotal' => $menuItemsTotal,
        'menuBalanceTotal' => $menuBalanceTotal,
        'has_prices' => ($menuItemsTotal > 0),
    ]);

    // STEP 2: If no cart price found, match by name from caracteristiques
    if ($menuItemsTotal <= 0) {

        Log::info('STEP2: No explicit prices, attempting name matching', [
            'offer_id' => $offer?->id,
            'will_use_caracteristiques' => true,
        ]);

        $caracHtml =
            $pricingResult['pricingData']['caracteristiques']
            ?? $offer?->caracteristiques
            ?? '';

        $lines = preg_split('/<\/p>|<br\s*\/?>|<\/li>/i', $caracHtml);

        Log::info('STEP2: Split characteristics into lines', [
            'offer_id' => $offer?->id,
            'lines_count' => count($lines),
            'caracHtml_length' => strlen($caracHtml),
        ]);

        foreach ($allCartDetails as $menuItem) {

            $qty = $menuItem->quantity ?? 1;

            $searchName = strtolower(trim(strip_tags(
                $menuItem->name
                ?? $menuItem->service_label
                ?? $menuItem->title
                ?? $offer?->name  // Fallback to offer name when cart_detail has no name
                ?? ''
            )));

            if (!$searchName) {
                Log::info('SKIP: searchName is empty', [
                    'offer_id' => $offer?->id,
                    'item_name' => $menuItem->name,
                    'item_service_label' => $menuItem->service_label,
                    'item_title' => $menuItem->title,
                    'offer_name' => $offer?->name,
                ]);
                continue;
            }

            Log::info('STEP2: Searching for name in lines', [
                'offer_id' => $offer?->id,
                'searchName' => $searchName,
                'qty' => $qty,
            ]);

            $bestMatch = null;
            $bestMatchPrice = null;
            $bestMatchBalance = 0;
            $bestMatchScore = 0;

            foreach ($lines as $line) {

                $cleanLine = strtolower(trim(strip_tags($line)));

                if (!$cleanLine) continue;

                // Try to find WORD-BASED match (handles spelling variations like "supevisée" vs "supervisée")
                // Split search name into words and check if all important words appear in line
                $searchWords = array_filter(preg_split('/\s+/', $searchName));
                $matchedWords = 0;
                $firstWordPos = PHP_INT_MAX;

                foreach ($searchWords as $word) {
                    if (strlen($word) > 2) {  // Only match words longer than 2 chars (skip "à", "et", etc)
                        $pos = strpos($cleanLine, $word);
                        if ($pos !== false) {
                            $matchedWords++;
                            if ($pos < $firstWordPos) {
                                $firstWordPos = $pos;
                            }
                        }
                    }
                }

                // If most words match, this is our line
                if ($matchedWords > 0 && $matchedWords >= ceil(count(array_filter(preg_split('/\s+/', $searchName), fn($w) => strlen($w) > 2)) / 2)) {
                    // Extract PRICE from THIS line
                    if (preg_match('/(\d+[\.,]?\d*)\s*€/u', $cleanLine, $m)) {
                        $price = (float) str_replace(',', '.', $m[1]);

                        // Also extract HOURS from this line (e.g., "2h" or "20h")
                        $balance = 0;
                        if (preg_match('/(\d+)\s*h/ui', $cleanLine, $bm)) {
                            $balance = (int) $bm[1];
                        }

                        // Score by word match count and position: prefer earlier matches
                        $matchScore = ($matchedWords * 1000) - $firstWordPos;

                        // Keep the best match
                        if ($matchScore > $bestMatchScore) {
                            $bestMatchScore = $matchScore;
                            $bestMatch = $cleanLine;
                            $bestMatchPrice = $price;
                            $bestMatchBalance = $balance;
                        }
                    }
                }
            }

            if ($bestMatchPrice !== null) {
                $menuItemsTotal += ($bestMatchPrice * $qty);
                $menuBalanceTotal += ($bestMatchBalance * $qty);  // Add balance

                $matchedLines[] = [
                    'name' => $searchName,
                    'line' => $bestMatch,
                    'price' => $bestMatchPrice,
                    'balance' => $bestMatchBalance,
                ];

                Log::info('✅ Menu item WORD-BASED match found WITH balance', [
                    'offer_id' => $offer?->id,
                    'search_name' => $searchName,
                    'matched_line' => $bestMatch,
                    'price' => $bestMatchPrice,
                    'balance' => $bestMatchBalance,
                    'match_score' => $bestMatchScore,
                    'qty' => $qty,
                ]);
            } else {
                Log::info('❌ Menu item NO WORD-BASED match found', [
                    'offer_id' => $offer?->id,
                    'search_name' => $searchName,
                    'available_lines_count' => count($lines),
                    'lines_preview' => array_slice($lines, 0, 3),
                ]);
            }
        }
    }

    if ($menuItemsTotal > 0) {
        $detailAmount = round($menuItemsTotal, 2);
        // Update balance if we found matched lines with balance info
        if ($menuBalanceTotal > 0) {
            $detailBalance = $menuBalanceTotal;
        }
        Log::info('✅ Menu offer: using extracted/matched amount and balance from name matching', [
            'offer_id' => $offer?->id,
            'amount' => $detailAmount,
            'balance' => $detailBalance,
            'item_count' => count($allCartDetails),
            'matched_lines' => $matchedLines,
        ]);
    } else {
        Log::info('⚠️  Menu name matching found NO PRICES, using fallback', [
            'offer_id' => $offer?->id,
            'menuItemsTotal' => $menuItemsTotal,
            'matchedLines_count' => count($matchedLines),
        ]);
        // FALLBACK: if name matching found no prices, extract the largest price from caracteristiques
        // (the main menu price, not component prices)
        $caracSource = $pricingResult['pricingData']['caracteristiques']
            ?? $offer?->caracteristiques
            ?? '';

        if ($caracSource) {
            try {
                if (preg_match_all('/(\d+[\.,]?\d*)\s*(?:&nbsp;|\s)*€/u', $caracSource, $m)) {
                    $prices = [];
                    foreach (($m[1] ?? []) as $v) {
                        $prices[] = (float) str_replace(',', '.', $v);
                    }
                    if (!empty($prices)) {
                        // Take the largest price (main menu item, not component)
                        $detailAmount = round(max($prices), 2);
                        Log::info('Menu offer: extracted MAX price from caracteristiques fallback', [
                            'offer_id' => $offer?->id,
                            'all_prices' => $prices,
                            'extracted_amount' => $detailAmount,
                            'reason' => 'name_matching_failed',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Menu offer fallback extraction failed', [
                    'offer_id' => $offer?->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    Log::info('🎯 EXITING MENU OFFER PROCESSING - FINAL RESULT', [
        'offer_id' => $offer?->id,
        'offer_name' => $offer?->name,
        'final_detailAmount' => $detailAmount,
        'final_detailBalance' => $detailBalance,
        'source' => ($menuItemsTotal > 0) ? 'cartDetails_or_caracteristiques' : 'fallback',
    ]);
                } else {
                    // Non-menu path: if fallback was used and generated an amount,
                    // override it by summing the actual prices from grouped cartDetails
                    if ($detailAmount > 0 && empty($offer?->final_price) && empty($offer?->original_price) && empty($offer?->agency_pricing)) {
                        $menuItemsTotal = 0.0;
                        foreach ($allCartDetails as $menuItem) {
                            // sum prices from each cartDetail
                            $itemPrice = $menuItem->price ?? $menuItem->final_price ?? 0;
                            $menuItemsTotal += ($itemPrice * ($menuItem->quantity ?? 1));
                        }
                        if ($menuItemsTotal > 0) {
                            Log::info('Offer: using summed cartDetail prices instead of fallback', [
                                'offer_id' => $offer?->id,
                                'fallback_amount' => $detailAmount,
                                'cartdetails_sum' => $menuItemsTotal,
                                'item_count' => count($allCartDetails),
                            ]);
                            $detailAmount = $menuItemsTotal;
                        }
                    }
                }

                // Multiply amount/balance by quantity (important for merged duplicate items)
                // BUT: for menu offers, don't multiply - prices should come per-item from frontend
                if (!$isMenuOffer) {
                    $detailAmount  = $detailAmount * $totalQuantity;
                    $detailBalance = $detailBalance * $totalQuantity;
                }

                Log::info('💰 Amount before installment split', [
                    'offer_id' => $offer?->id,
                    'detailAmount' => $detailAmount,
                    'detailBalance' => $detailBalance,
                    'isMenuOffer' => $isMenuOffer,
                    'tranches' => $tranches,
                    'selectedInstallment' => $selectedInstallment,
                ]);

                // Only split if we don't have agency installments (which were already handled)
                // Agency installments already extracted correct per-installment amount
                if ($tranches > 1 && $selectedInstallment && !$hasAgencyInstallments) {
                    $detailAmount  = self::splitInstallment($detailAmount, $selectedInstallment, $tranches);
                    $detailBalance = self::splitInstallment($detailBalance, $selectedInstallment, $tranches);
                }

                // if the frontend provided explicit amount/balance overrides in matching, use them
                if ($matching) {
                    // IMPORTANT: Only re-split if we did NOT extract from agency installments array
                    // If hasAgencyInstallments=true, amount AND balance were already correctly extracted/split
                    // from the installments array and splitting them again would be wrong (double-split bug)
                    if (isset($matching['installment_no']) && $tranches > 1 && !$hasAgencyInstallments) {
                        $detailAmount  = self::splitInstallment($offer?->final_price ?? 0, $matching['installment_no'], $tranches);
                        $detailBalance = self::splitInstallment($offer?->balance ?? 0, $matching['installment_no'], $tranches);
                    }
                    // Only override with explicit values if frontend provides them
                    if (isset($matching['amount'])) {
                        $detailAmount = $matching['amount'];
                    }
                    if (isset($matching['balance'])) {
                        $detailBalance = $matching['balance'];
                    }
                }

                // build attributes for sale creation; for a lone cart item we still
                // honour any provided amount/balance but fall back to our computed
                // values if the request did not include them (previous behavior
                // blindly ignored computed values when attributes were empty).
                if (count($groupedByOfferId) === 1) {
                    $saleAttrs = Arr::only($attributes, ['amount', 'balance']);
                    if (!isset($saleAttrs['amount']) || !isset($saleAttrs['balance'])) {
                        $saleAttrs = [
                            'amount'  => $detailAmount,
                            'balance' => $detailBalance,
                        ];
                    }
                } else {
                    $saleAttrs = [
                        'amount'  => $detailAmount,
                        'balance' => $detailBalance,
                    ];
                }

                if ($tranches > 1 && $selectedInstallment) {
                    $saleAttrs['installment_no']   = $selectedInstallment;
                    $saleAttrs['total_tranches']   = $tranches;
                }

                Log::info('Creating sale with attrs', [
                    'offer_id' => $offer?->id,
                    'offer_name' => $offer?->name,
                    'saleAttrs' => $saleAttrs,
                    'totalQuantity' => $totalQuantity,
                    'finalAmount' => $detailAmount,
                    'finalBalance' => $detailBalance,
                    'matchedAgency' => $pricingResult['matchedAgency'] ?? null,
                ]);

                // CRITICAL: Ensure sale always gets the computed amount from cartDetails
                if ($isMenuOffer && $detailAmount > 0) {
                    // Force the amount from cartDetails into saleAttrs for menu offers
                    $saleAttrs['amount'] = $detailAmount;
                    $saleAttrs['balance'] = $detailBalance;
                    Log::info('🔐 Menu offer: forcing cartDetail prices into sale', [
                        'offer_id' => $offer?->id,
                        'amount' => $detailAmount,
                        'balance' => $detailBalance,
                    ]);
                }

                $sales[] = StoreSaleRepo::run($saleAttrs, $cart);

                Log::info('🟢 Sale created with balance', [
                    'sale_id' => $sales[count($sales)-1]?->id,
                    'amount_in_sale' => $sales[count($sales)-1]?->amount,
                    'balance_in_sale' => $sales[count($sales)-1]?->balance,
                    'offer_id' => $offer?->id,
                    'matchedAgency' => $pricingResult['matchedAgency'] ?? null,
                ]);
            }

            // at least one sale must have been created
            if (empty($sales)) {
                DB::rollback();
                Log::error('Payment processing failed: no sales were created');
                return response()->json(['error' => 'Failed to create sales. Please try again later.'], 500);
            }

            // create payment intent using first sale (metadata still useful)
            // amount/balance should reflect the total of all individual entries
            $totalAmount = array_sum(array_map(fn($s) => $s->amount, $sales));
            $totalBalance = array_sum(array_map(fn($s) => $s->balance, $sales));
            $paymentIntent = $this->createCharge->run(['amount' => $totalAmount, 'balance' => $totalBalance], $sales[0]);

            DB::commit();
            // Return the client secret and both sales to the frontend
            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'sale'         => $sales[0],        // keep old key for backwards compat
                'sales'        => $sales,
            ]);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Payment processing failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle successful payment
     * @param string $paymentIntentId
     * @param Sale $sale
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function handleSuccessfulPayment(string $paymentIntentId, Sale $sale)
    {
        DB::beginTransaction();
        try {
            $cart = $sale->cart;
            $student = $sale->student;

            if (!$student) {
                throw new Exception('Student not found for this sale');
            }

            // create wallets and compute total balance using the actual sales
            $totalPaidBalance = 0;
            // assume sales were created in the same order as cart details
            $details = $cart->cartDetails->values();

            Log::info('🟣 handleSuccessfulPayment START', [
                'total_sales' => $cart->sales->count(),
                'total_details' => $details->count(),
            ]);

            foreach ($cart->sales as $index => $s) {
                $paidBalance = $s->balance ?? 0;
                $totalPaidBalance += $paidBalance;

                $cartDetail = $details->get($index);
                $tranches = $cartDetail?->tranches ?? 0;
                $selectedInstallment = $cartDetail?->selected_installment_no ?? null;

                // ensure we have a valid offer id before attempting wallet operations
                $offerId = $cartDetail?->offer_id ?? null;

                Log::info('🟤 Processing sale for wallet', [
                    'index' => $index,
                    'sale_id' => $s->id,
                    'sale_balance' => $s->balance,
                    'offer_id' => $offerId,
                    'offer_name' => $cartDetail?->offer?->name,
                ]);

                if (!$offerId) {
                    Log::warning('Skipping wallet update: missing offer_id for cart detail', [
                        'cart_detail' => $cartDetail,
                        'sale_id' => $s->id ?? null,
                        'index' => $index,
                    ]);
                    // continue without manipulating wallet
                    continue;
                }

                $walletAttrs = [
                    'balance' => $paidBalance,
                    'status' => WalletTypeEnum::ACTIVE->value,
                    'offer_id' => $offerId,
                    'balance_type' => $tranches > 1
                        ? WalletBalanceTypeEnum::INSTALLMENT->value
                        : WalletBalanceTypeEnum::FULL->value,
                ];

                if ($tranches > 1 && $selectedInstallment) {
                    $walletAttrs['installment_no'] = $selectedInstallment;
                    $walletAttrs['total_tranches'] = $tranches;
                    $walletAttrs['force_create'] = true;
                }

                Log::info('🟥 Wallet attributes', $walletAttrs);

                StoreOrEditWalletAction::run($student, $walletAttrs);

                Log::info('🟦 Wallet created', ['offer_id' => $offerId, 'balance' => $paidBalance]);
            }

            // update global student balance using actual paid amounts
            EditStudentRepo::run($student, [
                'balance' => $student->balance + $totalPaidBalance
            ]);

            // mark all sales attached to this cart as paid
            foreach ($cart->sales as $s) {
                EditSaleRepo::run($s, [
                    'payment_status' => SaleStatusEnum::PAID->value,
                    'payment_id' => $paymentIntentId
                ]);
            }

            $status = EditCartRepo::run($cart, [
                'status' => CartStatusEnum::PAID->value,
            ]);

            DB::commit();
            session()->flash('success', 'Payment completed.');
            return response()->json([
                'success' => $status,
                'sale' => $sale,
                'sales' => $cart->sales->all()
            ]);
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Payment success handling failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @throws Exception
     */
    public function refund(Sale $sale, array $attributes)
    {
        DB::beginTransaction();
        try {
            $offer = null;
            $status = SaleStatusEnum::REFUNDED->value;

            $student = $sale->student;

            if (isset($attributes['offer_id'])) {
                $status = SaleStatusEnum::PARTIAL_REFUND->value;
                // get offer
                $offer = FetchOffreRepo::run($attributes['offer_id']);
                $wallet = $offer->balance;

                // update balance eleve
                FetchWalletRepo::run($student->id, ['offer_id' => $attributes['offer_id']])
                    ->decrement('balance', $wallet);
            } else {
                foreach ($sale->cart->cartDetails as $cartDetail) {
                    $offer = $cartDetail->offer;
                    $amount = $offer->balance ?? $offer->final_price ?? $offer->original_price ?? 0;
                    StoreOrEditWalletAction::run($sale->student, [
                        'balance' => $amount,
                        'offer_id' => $cartDetail->offer_id
                    ]);
                }
                $wallet = $student?->balance > $sale->balance ? $student?->balance - $sale->balance : 0;
            }

            // update balance global eleve
            EditStudentRepo::run($student, [
                'balance' => $wallet
            ]);

            // update status order
            EditSaleRepo::run($sale, [
                'payment_status' => $status
            ]);

            // create refund
            $refund = $this->createRefund->run($sale, $offer?->final_price);

            DB::commit();
            return $refund;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
