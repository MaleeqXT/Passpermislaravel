<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Sale;

use App\Http\Controllers\Controller;
use App\Models\Roles\Student\User\Student;
use App\Repository\V2\Shared\Schedule\Sale\FetchAllSaleRepo;
use Illuminate\Http\JsonResponse;

class SaleDetailsController extends Controller
{
    /**
     * Return the authenticated student's paid and pending sales with their offers.
     * The student is resolved on the server so the frontend cannot ask for another
     * student's purchase history.
     */
    public function current(): JsonResponse
    {
        $student = auth()->user()?->student;

        if (!$student) {
            return response()->json([
                'message' => 'Profil élève introuvable.',
            ], 422);
        }

        $sales = FetchAllSaleRepo::run(['student_id' => $student->id]);

        return response()->json([
            'data' => $sales->items(),
        ]);
    }

    /**
     * Return sales with cart details for a given student.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function index(Student $student): JsonResponse
    {
        // Fetch sales for the student (repository will eager load cart and cartDetails)
        $sales = FetchAllSaleRepo::run(['student_id' => $student->id]);

        // Return only the items array so frontend can consume as result.data
        return response()->json([
            'data' => $sales->items(),
        ]);
    }
}
