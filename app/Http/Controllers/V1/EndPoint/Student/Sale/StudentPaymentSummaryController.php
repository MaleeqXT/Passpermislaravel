<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Sale;

use App\Enums\V2\Student\Schedule\Sale\CartStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SalePaymentMethodEnum;
use App\Http\Controllers\Controller;
use App\Models\Roles\Admin\Offer\Cart\Cart;
use App\Models\Roles\Admin\Offer\Order\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Stripe\StripeClient;

class StudentPaymentSummaryController extends Controller
{
    /**
     * Payment data for the authenticated student.  Installment records currently
     * have no due-date column (and installments_data has no date), so no dates
     * are fabricated here; upcoming_installments remains empty until a real
     * schedule source is introduced.
     */
    public function show(): JsonResponse
    {
        $student = auth()->user()?->student?->load('user');

        if (!$student) {
            return response()->json(['message' => 'Profil élève introuvable.'], 422);
        }

        $carts = Cart::query()
            ->where('student_id', $student->id)
            ->whereNotIn('status', [
                CartStatusEnum::CANCELLED->value,
                CartStatusEnum::REFUNDED->value,
                CartStatusEnum::ABANDONED->value,
                CartStatusEnum::EXPIRED->value,
            ])
            ->with(['cartDetails.offer'])
            ->get();

        // This matches the established order display: the selected cart-detail
        // price is authoritative, then the offer's normal price fallbacks apply.
        $total = $carts->sum(function (Cart $cart): float {
            return $cart->cartDetails->sum(function ($detail): float {
                $offer = $detail->offer;
                $price = $detail->price ?? $offer?->final_price ?? $offer?->discounted_price ?? $offer?->original_price ?? 0;
                $quantity = (float) ($detail->quantity ?: 1);

                return (float) $price * $quantity;
            });
        });

        $paidSales = Sale::query()
            ->where('student_id', $student->id)
            ->where('payment_status', SaleStatusEnum::PAID->value)
            ->orderByDesc('created_at')
            ->get();

        $paid = (float) $paidSales->sum('amount');
        $stripeCards = [];
        $paymentMethods = [];
        foreach ($paidSales as $sale) {
            $method = SalePaymentMethodEnum::tryFrom((int) $sale->payment_method);
            $lastFour = $method === SalePaymentMethodEnum::STRIP
                ? $this->stripeCardLastFour($sale->payment_id, $stripeCards)
                : null;
            $key = $method === SalePaymentMethodEnum::STRIP
                ? 'card:' . ($lastFour ?: $sale->payment_id ?: $sale->id)
                : 'method:' . ($method?->value ?? $sale->payment_method);

            if (!isset($paymentMethods[$key])) {
                $paymentMethods[$key] = [
                    'id' => $key,
                    'type' => match ($method) {
                        SalePaymentMethodEnum::STRIP => 'card',
                        SalePaymentMethodEnum::TRANSFER => 'bank',
                        SalePaymentMethodEnum::CASH => 'cash',
                        default => 'other',
                    },
                    'title' => $method?->label() ?? 'Moyen de paiement inconnu',
                    'last_four' => $lastFour,
                ];
            }
        }
        $ville = strtolower(trim((string) $student->user?->ville));
        $agency = str_contains($ville, 'toulouse') ? 'Toulouse' : (str_contains($ville, 'creil') ? 'Creil' : null);
        $boiteType = strtolower(trim((string) $student->boite_type));
        $trainingType = in_array($boiteType, ['1', 'automatic', 'automatique', 'auto', 'ba'], true) ? 'Permis BA' : 'Permis BM';

        return response()->json([
            'student' => [
                'id' => $student->id,
                'training_type' => $trainingType,
                'agency' => $agency,
                'registration_date' => $student->user?->created_at?->toDateString(),
            ],
            'payments' => [
                'paid' => round($paid, 2),
                'total' => round((float) $total, 2),
                'remaining' => round(max(0, $total - $paid), 2),
            ],
            'upcoming_installments' => [],
            'payment_methods' => array_values($paymentMethods),
            'payment_history' => $paidSales->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'date' => $sale->created_at?->toDateString(),
                'amount' => (float) $sale->amount,
                'payment_method' => SalePaymentMethodEnum::tryFrom((int) $sale->payment_method)?->label() ?? 'Non renseigné',
                'installment_no' => $sale->installment_no,
                'total_tranches' => $sale->total_tranches,
                'reference' => $sale->reference,
            ])->values(),
        ]);
    }

    /** Returns only Stripe's safe card suffix; card numbers are never stored or exposed. */
    private function stripeCardLastFour(?string $paymentIntentId, array &$cache): ?string
    {
        if (!$paymentIntentId || !str_starts_with($paymentIntentId, 'pi_')) {
            return null;
        }
        if (array_key_exists($paymentIntentId, $cache)) {
            return $cache[$paymentIntentId];
        }

        try {
            $stripe = new StripeClient(config('stripe.api_keys.secret_key'));
            $intent = $stripe->paymentIntents->retrieve($paymentIntentId);
            $chargeId = is_string($intent->latest_charge) ? $intent->latest_charge : $intent->latest_charge?->id;
            if ($chargeId) {
                $charge = $stripe->charges->retrieve($chargeId);
                return $cache[$paymentIntentId] = $charge->payment_method_details?->card?->last4;
            }

            if ($intent->payment_method) {
                $paymentMethod = $stripe->paymentMethods->retrieve($intent->payment_method);
                return $cache[$paymentIntentId] = $paymentMethod->card?->last4;
            }
        } catch (\Throwable) {
            // Older imported sales or an unavailable provider must not break the dashboard.
        }

        return $cache[$paymentIntentId] = null;
    }

    /** Stream the selected paid receipt using the existing student invoice view. */
    public function download(Sale $sale): Response
    {
        $student = auth()->user()?->student;
        abort_unless($student && $sale->student_id === $student->id, 403);
        abort_unless($sale->payment_status === SaleStatusEnum::PAID->value, 404);

        $sale->load([
            'student.user',
            'cart.cartDetails' => fn ($query) => $query->withTrashed()->with(['offer' => fn ($offerQuery) => $offerQuery->withTrashed()]),
        ]);

        $relatedSales = filled($sale->payment_id)
            ? Sale::query()
                ->where('student_id', $student->id)
                ->where('payment_id', $sale->payment_id)
                ->where('payment_status', SaleStatusEnum::PAID->value)
                ->with(['cart.cartDetails' => fn ($query) => $query->withTrashed()->with(['offer' => fn ($offerQuery) => $offerQuery->withTrashed()])])
                ->get()
            : collect([$sale]);

        $invoiceData = $sale->toArray();
        $invoiceData['related_sales'] = $relatedSales->toArray();

        return Pdf::loadView('pdf.normal.student-invoice', $invoiceData)
            ->stream('facture-' . Carbon::parse($sale->created_at)->format('Y-m-d') . '.pdf');
    }
}
