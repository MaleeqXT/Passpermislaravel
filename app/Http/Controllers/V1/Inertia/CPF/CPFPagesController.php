<?php

namespace App\Http\Controllers\V1\Inertia\CPF;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Cpf\StoreAttestaionRequest;
use App\Http\Requests\V1\Cpf\StoreTestRequest;
use App\Http\Requests\V1\Cpf\UpdateFormCpfRequest;
use App\Models\CPFDocumentInfo;
use App\Notifications\V1\CPF\EnvoiDocumentCpfNotification;
use App\Notifications\V1\CPF\FinDocumentCpfNotification;
use App\Repository\V2\Cpf\EditCPFDocumentInfoRepo;
use App\Repository\V2\Cpf\FetchAllCpfFormRepo;
use App\Repository\V2\Cpf\StoreCpfDocumentInfoRepo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Roles\Admin\Area\Zone;
use Inertia\Inertia;
use Inertia\Response;

class CPFPagesController extends Controller
{
    /** List the CPF requests displayed in the React administration page. */
    public function adminRecords(): JsonResponse
    {
        return response()->json([
            'data' => CPFDocumentInfo::query()->latest()->get(),
        ]);
    }

    /** Save the values managed from the CPF administration drawer. */
    public function updateAdminRecord(Request $request, CPFDocumentInfo $CPFDocumentInfo): JsonResponse
    {
        $data = $request->validate([
            'numero_cpf' => ['nullable', 'string', 'max:255'],
            'box_type' => ['nullable', 'in:Manuel,Automatic'],
            'offre' => ['nullable', 'string', 'max:255'],
            'reservations' => ['nullable', 'array'],
            'reservations.*.date' => ['nullable', 'date'],
            'reservations.*.duration' => ['nullable', 'numeric', 'min:0'],
            'reservations.*.startTime' => ['nullable', 'date_format:H:i'],
            'reservations.*.endTime' => ['nullable', 'date_format:H:i'],
        ]);

        $reservations = collect($data['reservations'] ?? [])
            ->filter(fn (array $reservation) => filled($reservation['date'] ?? null))
            ->map(fn (array $reservation) => [
                'date' => $reservation['date'],
                'duration' => $reservation['duration'] ?? null,
                // Keep the legacy key so the existing CPF reservation PDF remains compatible.
                'houre' => $reservation['duration'] ?? null,
                'startTime' => $reservation['startTime'] ?? null,
                'endTime' => $reservation['endTime'] ?? null,
            ])
            ->values()
            ->all();

        $CPFDocumentInfo->update([
            'numero_cpf' => $data['numero_cpf'] ?? null,
            'boite' => filled($data['box_type'] ?? null) ? [
                'id' => $data['box_type'] === 'Automatic' ? 'auto' : 'manual',
                'value' => $data['box_type'],
            ] : null,
            'offre' => $data['offre'] ?? null,
            'reservations' => $reservations,
        ]);

        return response()->json([
            'message' => 'Le formulaire CPF a été mis à jour.',
            'data' => $CPFDocumentInfo->fresh(),
        ]);
    }

    /** Send the document email from the React CPF administration page. */
    public function sendDocumentsAdmin(CPFDocumentInfo $CPFDocumentInfo): JsonResponse
    {
        Notification::route('mail', data_get($CPFDocumentInfo->test_pro, 'email'))
            ->notify(new EnvoiDocumentCpfNotification($CPFDocumentInfo));

        return response()->json(['message' => 'Les documents CPF ont été envoyés par e-mail.']);
    }

    /** Send the completion-certificate email from the React CPF administration page. */
    public function sendCompletionCertificateAdmin(CPFDocumentInfo $CPFDocumentInfo): JsonResponse
    {
        Notification::route('mail', data_get($CPFDocumentInfo->test_pro, 'email'))
            ->notify(new FinDocumentCpfNotification($CPFDocumentInfo));

        return response()->json(['message' => "L'attestation de fin de formation a été envoyée par e-mail."]);
    }

    /** Public data used by the two-step CPF request in the website SPA. */
    public function publicZones(): JsonResponse
    {
        return response()->json([
            'data' => Zone::query()
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /** Store the completed public CPF positioning form. */
    public function storePublicPositioning(Request $request): JsonResponse
    {
        $data = $request->validate([
            'test_pro' => ['required', 'array'],
            'test_pro.name' => ['required', 'string', 'max:150'],
            'test_pro.email' => ['required', 'email', 'max:255'],
            'test_pro.phone' => ['required', 'string', 'max:40'],
            'test_pro.zone_id' => ['required', 'exists:zones,id'],
            'test_pro.zone_name' => ['required', 'string', 'max:150'],
            'test_pro.recall_preference' => ['nullable', 'string', 'max:255'],
            'test_pro.available_days' => ['nullable', 'string', 'max:255'],
            'test_pro.signature' => ['nullable', 'string'],
            'test_pro.answers' => ['required', 'array'],
        ]);

        $info = CPFDocumentInfo::query()->create([
            'test_pro' => $data['test_pro'],
        ]);

        return response()->json([
            'message' => 'Votre demande CPF a été enregistrée avec succès.',
            'data' => $info,
        ], 201);
    }

    /**
     * @return Response
     */
    public function index()
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/cpf/HomePage');
    }

    /**
     * @return Response
     */
    public function indexAdmin(FetchAllCpfFormRepo $repo)
    {
        Inertia::setRootView('espace-admin');
        return Inertia::render('features/general/cpf-form/CPFFormPage', [
            'cpfs' => $repo->run(request()->all()),
        ]);
    }

    /**
     * @return Response
     */
    public function attestationHonneur(CPFDocumentInfo $CPFDocumentInfo)
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/cpf/AttestationPage', [
            'info' => $CPFDocumentInfo
        ]);
    }

    /**
     * @return Response
     */
    public function contactFormation(CPFDocumentInfo $CPFDocumentInfo)
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/cpf/ContactFormationPage', [
            'info' => $CPFDocumentInfo
        ]);
    }


    /**
     * @return Response
     */
    public function finish()
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/cpf/SuccessPage');
    }

    /**
     * @return Response
     */
    public function formationPermisBInfo()
    {
        Inertia::setRootView('espace-client');
        return Inertia::render('features/cpf/FormationPermisBPage');
    }

    protected function ensureFormationPermisBPdfExists(): string
    {
        $pdfPath = storage_path('app/public/pdf/permis-b.pdf');
        $pdfDirectory = dirname($pdfPath);

        if (! File::exists($pdfDirectory)) {
            File::makeDirectory($pdfDirectory, 0755, true);
        }

        $pdf = Pdf::loadView('pdf.cpf.formation-permis-b')->setPaper('A4');
        File::put($pdfPath, $pdf->output());

        return $pdfPath;
    }


    /**
     * @param StoreTestRequest $request
     * @param StoreCpfDocumentInfoRepo $action
     * @return RedirectResponse
     * @throws Exception
     */
    public function storeTestPos(StoreTestRequest $request, StoreCpfDocumentInfoRepo $action): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $CPFDocumentInfo = $action->run(['test_pro' => $request->validated()]);
            DB::commit();
            session()->flash('success', flashMessage());
            return redirect()->route('forms-cpf.contact.formation.index', $CPFDocumentInfo->id);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }

    /**
     * @param CPFDocumentInfo $CPFDocumentInfo
     * @param StoreAttestaionRequest $request
     * @param EditCPFDocumentInfoRepo $action
     * @return RedirectResponse
     * @throws Exception
     */
    public function storeAttestaion(CPFDocumentInfo $CPFDocumentInfo, StoreAttestaionRequest $request, EditCPFDocumentInfoRepo $action): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $CPFDocumentInfo = $action->run($CPFDocumentInfo, ['attestation_honneur' => $request->all()]);
            DB::commit();
            session()->flash('success', flashMessage());
            return redirect()->route('forms-cpf.finish')->with([
                'success' => 'Votre attestation d\'honneur a été enregistrée avec succès.',
            ]);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }

    /**
     * @param CPFDocumentInfo $CPFDocumentInfo
     * @param UpdateFormCpfRequest $request
     * @param EditCPFDocumentInfoRepo $action
     * @return RedirectResponse
     * @throws Exception
     */
    public function update(CPFDocumentInfo $CPFDocumentInfo, UpdateFormCpfRequest $request, EditCPFDocumentInfoRepo $action): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $CPFDocumentInfo = $action->run($CPFDocumentInfo, $request->validated());
            DB::commit();
            session()->flash('success', flashMessage());
            return redirect()->back()->with([
                'success' => 'Votre formulaire a été mis à jour avec succès.',
            ]);
        } catch (Exception $e) {
            DB::rollback();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }


    /**
     * @param CPFDocumentInfo $CPFDocumentInfo
     * @return \Illuminate\Http\Response
     */
    public function attestationHonneurPdf(CPFDocumentInfo $CPFDocumentInfo)
    {

        $pdf = Pdf::loadView('pdf.cpf.attestation-honneur', ['data' => $CPFDocumentInfo])->setPaper('A5');

        return $pdf->stream('attestation-honneur.pdf');
    }


    /**
     * @param CPFDocumentInfo $CPFDocumentInfo
     * @return \Illuminate\Http\Response
     */
    public function storeTestPosPdf(CPFDocumentInfo $CPFDocumentInfo)
    {

        $pdf = Pdf::loadView('pdf.cpf.test-pos', ['data' => $CPFDocumentInfo])->setPaper('A5');

        return $pdf->stream('Test de positionnement en ligne CPF PPF.pdf');
    }


    /**
     * @param CPFDocumentInfo $CPFDocumentInfo
     * @return \Illuminate\Http\Response
     */
    public function contactFormationPdf(CPFDocumentInfo $CPFDocumentInfo)
    {
        $CPFoffres = [
            'manual' => [
                1 => [
                    'id' => 1,
                    'name' => 'FORFAIT 6 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs et les heures de conduite.",
                    'price_without_code' => 550.00,
                    'price_with_code' => 750.00,
                ],
                2 => [
                    'id' => 2,
                    'name' => 'FORFAIT ACCÉLÉRÉ 12 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 1080.00,
                    'price_with_code' => 1280.00,
                ],
                3 => [
                    'id' => 3,
                    'name' => 'FORFAIT ACCÉLÉRÉ 22 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 1950.00,
                    'price_with_code' => 2150.00,
                ],
                4 => [
                    'id' => 4,
                    'name' => 'FORFAIT ACCÉLÉRÉ 27 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 2390.00,
                    'price_with_code' => 2590.00,
                ],
                5 => [
                    'id' => 5,
                    'name' => 'FORFAIT ACCÉLÉRÉ 32 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 2830.00,
                    'price_with_code' => 3030.00,
                ],
                6 => [
                    'id' => 6,
                    'name' => 'FORFAIT ACCÉLÉRÉ 37 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 3270.00,
                    'price_with_code' => 3470.00,
                ],
                7 => [
                    'id' => 7,
                    'name' => 'FORFAIT ACCÉLÉRÉ 42 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 3720.00,
                    'price_with_code' => 3920.00,
                ],
            ],
            'auto' => [
                8 => [
                    'id' => 8,
                    'name' => 'FORFAIT 7 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs et les heures de conduite.",
                    'price_without_code' => 750.00,
                    'price_with_code' => 950.00,
                ],
                9 => [
                    'id' => 9,
                    'name' => 'FORFAIT ACCÉLÉRÉ 15 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 1490.00,
                    'price_with_code' => 1690.00,
                ],
                10 => [
                    'id' => 10,
                    'name' => 'FORFAIT ACCÉLÉRÉ 22 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 2150.00,
                    'price_with_code' => 2350.00,
                ],
                11 => [
                    'id' => 11,
                    'name' => 'FORFAIT ACCÉLÉRÉ 27 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 2490.00,
                    'price_with_code' => 2690.00,
                ],
                12 => [
                    'id' => 12,
                    'name' => 'FORFAIT ACCÉLÉRÉ 32 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 2930.00,
                    'price_with_code' => 3130.00,
                ],
                13 => [
                    'id' => 13,
                    'name' => 'FORFAIT ACCÉLÉRÉ 37 HEURES',
                    'description' => "Ce forfait comprend l’inscription, les frais administratifs, les cours de code intensif (10H en présentiel), les heures de conduite et l’accompagnement à l’examen en priorité sur le planning.",
                    'price_without_code' => 3370,
                    00, // à compléter si besoin
                    'price_with_code' => 3570,
                    00, // à compléter si besoin
                ],
            ],
        ];


        $offre = $CPFoffres[$CPFDocumentInfo?->boite['id'] ?? null][$CPFDocumentInfo?->offre] ?? null;
        $pdf = Pdf::loadView('pdf.cpf.contact-formation', ['data' => $CPFDocumentInfo, 'offre' => $offre])->setPaper('A5');

        return $pdf->stream('contrat-formation.pdf');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function formationPermisBPdf()
    {
        $pdfPath = $this->ensureFormationPermisBPdfExists();

        return response()->download($pdfPath, 'permis-b.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }


    /**
     * @param CPFDocumentInfo $CPFDocumentInfo
     * @return \Illuminate\Http\Response
     */
    public function reservationsPdf(CPFDocumentInfo $CPFDocumentInfo)
    {
        $pdf = Pdf::loadView('pdf.cpf.reservations', ['data' => $CPFDocumentInfo])->setPaper('A5');

        return $pdf->stream('reservations.pdf');
    }

    /**
     * @param CPFDocumentInfo $CPFDocumentInfo
     * @return \Illuminate\Http\Response
     */
    public function affPdf(CPFDocumentInfo $CPFDocumentInfo)
    {
        //first Reservation
        $reservations = collect($CPFDocumentInfo->reservations);
        // Filter out null dates first
        $validReservations = $reservations->filter(fn($res) => !empty($res['date']))->sortBy('date');



        // Sum total hours
        $totalHours = $validReservations->sum(function ($res) {
            return (float) $res['houre'];
        });

        // Get first and last
        $reservation = [
            'first' => Carbon::parse($validReservations->first()['date'])->format('d/m/Y'),
            'end' => Carbon::parse($validReservations->last()['date'])->format('d/m/Y'),
            'total_hours' => $totalHours,
        ];

        $pdf = Pdf::loadView('pdf.cpf.aff', ['data' => $CPFDocumentInfo, 'reservation' => $reservation])->setPaper('A5');

        return $pdf->stream('attestation-fin-formation.pdf');
    }


    public function envoyerDocument(CPFDocumentInfo $CPFDocumentInfo)
    {
        Notification::route('mail', $CPFDocumentInfo->test_pro['email'])
            ->notify(new EnvoiDocumentCpfNotification($CPFDocumentInfo));

        return redirect()->back()->with([
            'success' => 'Votre formulaire a été mis à jour avec succès. Vous allez recevoir un email contenant le lien de téléchargement de votre document CPF.',
        ]);
    }

    public function envoyerFinFormation(CPFDocumentInfo $CPFDocumentInfo)
    {
        Notification::route('mail', $CPFDocumentInfo->test_pro['email'])
            ->notify(new FinDocumentCpfNotification($CPFDocumentInfo));
        return redirect()->back()->with([
            'success' => 'Votre formulaire a été mis à jour avec succès. Vous allez recevoir un email contenant le lien de téléchargement de votre document CPF.',
        ]);
    }
}
