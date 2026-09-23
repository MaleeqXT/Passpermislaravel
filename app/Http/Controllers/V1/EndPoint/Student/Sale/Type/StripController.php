<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Sale\Type;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Offre\Sale\RefundSaleRequest;
use App\Http\Requests\V1\Student\Offre\Sale\Strip\PaymentIntentRequest;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Student\User\Student;
use App\Services\Payment\Strip\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Exception;

class StripController extends Controller
{

    public function index()
    {
        // // Inertia::setRootView('front');
        return Inertia::render('features/payment/PaymentsPage');
    }

    /**
     * success response method.
     *
     * @param PaymentIntentRequest $request
     * @param PaymentService $service
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface|ValidationException
     */

    public function store(PaymentIntentRequest $request, PaymentService $service)
    {
        return $service->action($request->validated(), $this->resolveTargetStudent($request->input('student_id')));
    }

    /**
     * @throws ValidationException
     * @throws \Exception
     */
    public function refund(Sale $sale, RefundSaleRequest $request, PaymentService $service)
    {
        return $service->refund($sale, $request->validated());
    }

    /**
     * Handle successful payment
     * @param Request $request
     * @param PaymentService $service
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function handleSuccess(Request $request, PaymentService $service)
    {
        $validated = $request->validate([
            'paymentIntentId' => ['required', 'string'],
            'saleId' => ['required', 'uuid'],
            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
        ]);
        $student = $this->resolveTargetStudent($validated['student_id'] ?? null);
        $sale = Sale::query()->where('student_id', $student->id)->findOrFail($validated['saleId']);
        return $service->handleSuccessfulPayment($request->paymentIntentId, $sale);
    }

    /** A student may only pay their own cart; staff may pay for the dashboard student. */
    private function resolveTargetStudent(?string $requestedStudentId = null): Student
    {
        $user = auth()->user();
        $student = $user?->student ?? Student::query()->where('user_id', $user?->id)->first();

        if (!$student && $requestedStudentId && $user?->hasAnyRole(['admin', 'super-admin', 'secretary'])) {
            $student = Student::query()->find($requestedStudentId);
        }

        if (!$student) {
            throw new \RuntimeException('Profil élève introuvable pour ce paiement.');
        }

        return $student;
    }
}
