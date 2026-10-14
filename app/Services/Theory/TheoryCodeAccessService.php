<?php

namespace App\Services\Theory;

use App\Enums\V2\Student\Schedule\Offre\OffreTypeStatusEnum;
use App\Enums\V2\Student\Schedule\Sale\SaleStatusEnum;
use App\Models\Theory\TheoryManualCodeAccess;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Student\User\Student;

/** Resolves Code access from the application's existing paid-package data. */
class TheoryCodeAccessService
{
    public function hasAccess(Student $student): bool
    {
        return $this->hasPaidPackageAccess($student) || $this->manualAccessFor($student) !== null;
    }

    /** Existing online/offline paid sales are still the primary entitlement. */
    public function hasPaidPackageAccess(Student $student): bool
    {
        return Sale::query()
            ->where('student_id', $student->id)
            ->where('payment_status', SaleStatusEnum::PAID->value)
            ->whereHas('cart.cartDetails.offer', function ($query) {
                // A withdrawn package must remain usable by students who already paid.
                $query->withTrashed()->where('type', OffreTypeStatusEnum::CODE->value);
            })
            ->exists();
    }

    /**
     * Cash payments are recorded separately so that staff do not have to
     * fabricate an online cart or a Stripe sale just to open Code access.
     */
    public function manualAccessFor(Student $student): ?TheoryManualCodeAccess
    {
        return TheoryManualCodeAccess::query()
            ->active()
            ->where('student_id', $student->id)
            ->latest('granted_at')
            ->first();
    }
}
