<?php

namespace App\Http\Controllers\V1\EndPoint\RdvPermis;

use App\Exceptions\RdvPermisApiException;
use App\Http\Controllers\Controller;
use App\Services\RdvPermis\ExamService;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ExamController extends Controller
{
    public function index(Request $request, ExamService $exams): JsonResponse
    {
        $validated = $request->validate([
            'groupePermis' => ['bail', 'required', 'in:A,B'],
            'date' => ['bail', 'required', 'date_format:Y-m-d'],
        ]);

        return $this->respond(fn () => $exams->all($request->user(), $validated['groupePermis'], $validated['date']));
    }

    public function show(Request $request, string $examenId, ExamService $exams): JsonResponse
    {
        return $this->respond(fn () => $exams->find($request->user(), $examenId));
    }

    public function destroy(Request $request, string $examenId, ExamService $exams): JsonResponse
    {
        return $this->respond(fn () => $exams->cancel($request->user(), $examenId));
    }

    public function permutables(Request $request, ExamService $exams): JsonResponse
    {
        $validated = $request->validate([
            'examenPermuteId' => ['bail', 'required', 'string'],
            'date' => ['bail', 'required', 'date_format:Y-m-d'],
        ]);

        return $this->respond(fn () => $exams->permutables($request->user(), $validated['examenPermuteId'], $validated['date']));
    }

    public function replacementAllowance(Request $request, ExamService $exams): JsonResponse
    {
        $validated = $request->validate([
            'groupePermis' => ['bail', 'required', 'in:A,B'],
            'date' => ['bail', 'required', 'date_format:Y-m-d'],
        ]);

        return $this->respond(fn () => $exams->replacementAllowance($request->user(), $validated['groupePermis'], $validated['date']));
    }

    public function dates(Request $request, ExamService $exams): JsonResponse
    {
        $validated = $request->validate([
            'groupePermis' => ['bail', 'required', 'in:A,B,CE'],
            'dateDebut' => ['bail', 'required', 'date_format:Y-m-d'],
            'creneauSansCandidat' => ['nullable', 'boolean'],
        ]);

        return $this->respond(fn () => $exams->dates(
            $request->user(),
            $validated['groupePermis'],
            $validated['dateDebut'],
            $validated['creneauSansCandidat'] ?? null,
        ));
    }

    public function paginated(Request $request, ExamService $exams): JsonResponse
    {
        $validated = $request->validate([
            'dateDebut' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
            'parPage' => ['nullable', 'integer', 'min:1'],
        ]);

        return $this->respond(fn () => $exams->paginated($request->user(), array_filter([
            'groupe-permis' => 'CE',
            'statut' => 'PROGRAMME',
            'date-debut' => $validated['dateDebut'] ?? null,
            'page' => $validated['page'] ?? null,
            'par-page' => $validated['parPage'] ?? null,
        ], static fn (mixed $value): bool => $value !== null)));
    }

    public function replace(Request $request, ExamService $exams): JsonResponse
    {
        $validated = $request->validate([
            'examenId' => ['bail', 'required', 'string'],
            'candidatRemplacantId' => ['bail', 'required', 'string'],
        ]);

        return $this->respond(fn () => $exams->replace($request->user(), $validated['examenId'], $validated['candidatRemplacantId']));
    }

    public function permute(Request $request, ExamService $exams): JsonResponse
    {
        $validated = $request->validate([
            'examenId1' => ['bail', 'required', 'string'],
            'examenId2' => ['bail', 'required', 'string'],
        ]);

        return $this->respond(fn () => $exams->permute($request->user(), $validated['examenId1'], $validated['examenId2']));
    }

    /** @param callable(): ClientResponse $operation */
    private function respond(callable $operation): JsonResponse
    {
        try {
            $response = $operation();

            return response()->json($response->json(), $response->status());
        } catch (RdvPermisApiException $exception) {
            return response()->json(['message' => $exception->userMessage], $exception->responseStatus);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }
    }
}
