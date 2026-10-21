<?php

namespace App\Http\Controllers\V1\EndPoint\Student\Exam;

use App\Http\Controllers\Controller;
use App\Services\Student\Exam\StudentExamInformationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentExamController extends Controller
{
    /**
     * Return exam information for the currently authenticated student only.
     */
    public function index(Request $request, StudentExamInformationService $examInformation): JsonResponse
    {
        $student = $request->user()?->student;

        if (! $student) {
            return response()->json([
                'message' => 'Aucun dossier élève associé à ce compte.',
            ], 404);
        }

        return response()->json(['data' => $examInformation->for($student)]);
    }
}
