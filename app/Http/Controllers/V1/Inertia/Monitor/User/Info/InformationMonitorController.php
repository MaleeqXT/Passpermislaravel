<?php

namespace App\Http\Controllers\V1\Inertia\Monitor\User\Info;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Monitor\User\Info\Car\StoreOrUpdateDocumentProRequest;
use App\Http\Requests\V1\Monitor\User\Info\Car\StoreOrUpdateDocumentRequest;
use App\Models\Roles\Monitor\User\Monitor;
use App\Repository\V2\Monitor\Instructor\Certification\FetchAllCertificationRepo;
use App\Repository\V2\Monitor\Instructor\Certification\StoreOrEditCertificationRepo;
use App\Repository\V2\Monitor\Instructor\Document\FetchInstructorDocumentRepo;
use App\Repository\V2\Monitor\Instructor\Document\StoreOrEditInstructorDocumentRepo;
use App\Repository\V2\Monitor\Instructor\IdentityRecord\FetchIdentityRecordRepo;
use App\Repository\V2\Monitor\Instructor\IdentityRecord\StoreOrEditIdentityRecordRepo;
use App\Repository\V2\Monitor\Instructor\License\FetchAllLicenseRepo;
use App\Repository\V2\Monitor\Instructor\License\StoreOrEditLicenseRepo;
use App\Repository\V2\Monitor\Instructor\Permission\StoreOrEditPermissionInstructorRepo;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InformationMonitorController extends Controller
{

    /**
     * @param Monitor $monitor
     * @return Response
     */
    public function documents(Monitor $monitor): Response
    {

        return Inertia::render('features/roles/monitors/view/MonitorDocumentsPage', [
            'pieceIdentite' => FetchIdentityRecordRepo::run(array_merge(request()->all(), ['monitor_id' => $monitor->id])),
            'permis' => FetchAllLicenseRepo::run(array_merge(request()->all(), ['monitor_id' => $monitor->id])),
            'diplom' => FetchAllCertificationRepo::run(array_merge(request()->all(), ['monitor_id' => $monitor->id])),
            'professionel' => FetchInstructorDocumentRepo::run(array_merge(request()->all(), ['monitor_id' => $monitor->id])),
            'monitor' => $monitor->load('user')
        ]);
    }

    /**
     * @param Monitor $monitor
     * @param StoreOrUpdateDocumentRequest $request
     * @param StoreOrUpdateDocumentProRequest $requestPro
     * @return RedirectResponse
     * @throws Exception
     */
    public function documentsUpdate(
        Monitor                         $monitor,
        StoreOrUpdateDocumentRequest    $request,
        StoreOrUpdateDocumentProRequest $requestPro,
    ): RedirectResponse {
        DB::beginTransaction();
        try {
            // @todo reda refactor this code
            StoreOrEditIdentityRecordRepo::run($monitor, $request->only('media_piece_identite'));
            StoreOrEditLicenseRepo::run($monitor, $request->only('media_permis'));
            StoreOrEditCertificationRepo::run($monitor, $request->only('media_diplom'));
            $documentPro = StoreOrEditInstructorDocumentRepo::run($monitor, $requestPro->only(['denomination_social', 'forme_juridique', 'siret', 'num_autorisation', 'date_creation']));
            if ($request->get('autorisations'))
                StoreOrEditPermissionInstructorRepo::run($documentPro, $requestPro->get('autorisations'));
            DB::commit();
            return redirect()->back()->with('success', flashMessage());
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', flashMessage('error'));
            throw $e;
        }
    }
}
