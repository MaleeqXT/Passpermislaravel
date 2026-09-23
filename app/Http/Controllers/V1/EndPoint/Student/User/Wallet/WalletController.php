<?php

namespace App\Http\Controllers\V1\EndPoint\Student\User\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Student\Offre\Wallet\IncOrDecWalletRequest;
use App\Http\Requests\V1\Student\Offre\Wallet\UpdateWalletsRequest;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Student\User\Wallet;
use App\Repository\V2\Student\Schedule\Training\Wallet\EditWalletRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\FetchAllStudentWalletRepo;
use App\Repository\V2\Student\Schedule\Training\Wallet\IncOrDecWalletRepo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    /** Return the authenticated student's purchased offers and usable balances. */
    public function currentStudentWallets(Request $request): JsonResponse
    {
        $user = auth()->user();
        $student = $user?->student
            ?? Student::query()->where('user_id', $user?->id)->first();

        $requestedStudentId = $request->validate([
            'student_id' => ['nullable', 'uuid'],
        ])['student_id'] ?? null;

        if (!$student && $requestedStudentId && $user?->hasAnyRole(['admin', 'super-admin', 'secretary'])) {
            $student = Student::query()->find($requestedStudentId);
        }

        if (!$student) {
            return response()->json([
                'message' => 'Le compte connecté ne possède pas de profil élève.',
                'data' => [],
            ], 422);
        }

        return response()->json(FetchAllStudentWalletRepo::run($student, request()->all()));
    }


    /**
     * @param Student $student
     * @return JsonResponse
     */
    public function getBalanceByStudent(Student $student): JsonResponse
    {
        return response()->json(FetchAllStudentWalletRepo::run($student, request()->all()));
    }


    /**
     * @param Wallet $wallet
     * @param UpdateWalletsRequest $request
     * @return bool
     * @throws Exception
     */

    public function update(Wallet $wallet, UpdateWalletsRequest $request): bool
    {
        DB::beginTransaction();
        try {
            $wallet = EditWalletRepo::run($wallet, $request->validated());

            DB::commit();
            session()->flash('success', 'Le Balance est bien Modifier');

            return $wallet;
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', 'Une erreur est survenue lors de la modification du Balance');
            throw $e;
        }
    }

    /**
     * @param Student $student
     * @param IncOrDecWalletRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function balanceManaging(Student $student, IncOrDecWalletRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // create new Comment
            $wallet = IncOrDecWalletRepo::run($student, $request->get('offer_id'), $request->get('status'), $request->get('balance'));
            DB::commit();
            return response()->json([
                'message' => 'Operation effectuee avec succes',
                'data' => $wallet
            ]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
