<?php

namespace App\Repository\V2\Shared\Schedule\Sale;

use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Student\User\Student;
use App\Services\SatisfactionLifecycleService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EditSaleRepo
{
    /**
     * @param Sale $sale
     * @param array $attributes
     * @return Sale
     */
    public static function run(Sale $sale, array $attributes): Sale|null
    {
        try {
            $wasPaid = (int) $sale->payment_status === SaleStatusEnum::PAID->value;
            $updated = tap($sale)->update($attributes)->refresh();

            // Every payment method eventually records the same paid Sale
            // state.  Reconcile after the surrounding payment transaction has
            // committed, so a confirmed €38/€45 first-hour sale cannot miss
            // the Before-survey notification if it was paid by a path other
            // than the current Stripe/PayPal controllers.
            if (! $wasPaid && (int) $updated->payment_status === SaleStatusEnum::PAID->value) {
                self::reconcileSatisfactionAfterPayment($updated->student_id, $updated->id);
            }

            return $updated;
        } catch (Exception $e) {
            // Log the exception message for debugging purposes
            info('Failed to update : ' . $e->getMessage());
            return null;
        }

    }

    private static function reconcileSatisfactionAfterPayment(?string $studentId, string $saleId): void
    {
        if (! $studentId) {
            return;
        }

        DB::afterCommit(static function () use ($studentId, $saleId): void {
            try {
                $student = Student::query()->with('user')->find($studentId);

                if ($student) {
                    app(SatisfactionLifecycleService::class)->evaluateAll($student);
                }
            } catch (Throwable $exception) {
                // A notification failure must never reverse a confirmed
                // payment. The lifecycle remains recoverable from the
                // student's pending-surveys endpoint on the next request.
                Log::warning('Satisfaction reconciliation after payment failed.', [
                    'sale_id' => $saleId,
                    'student_id' => $studentId,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }
}
