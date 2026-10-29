<?php

use App\Http\Controllers\V1\EndPoint\Bilan\BilanController;
use App\Http\Controllers\V1\EndPoint\Monitor\FormationController;
use App\Http\Controllers\V1\EndPoint\Monitor\Invoice\InvoiceController;
use App\Http\Controllers\V1\EndPoint\Monitor\Rating\RatingMonitorController;
use App\Http\Controllers\V1\EndPoint\Monitor\Reservation\MonitorCancellationController;
use App\Http\Controllers\V1\EndPoint\Monitor\Reservation\MonitorPlanningCalendarController;
use App\Http\Controllers\V1\EndPoint\Monitor\Reservation\MonitorPlanningPdfController;
use App\Http\Controllers\V1\EndPoint\Monitor\Reservation\ReservationMonitorController;
use App\Http\Controllers\V1\EndPoint\Monitor\StudentProgressController;
use App\Http\Controllers\V1\EndPoint\Student\Info\ListStudentByMonitorController;
use App\Http\Controllers\V1\EndPoint\Student\Proposition\ProposalTrainingController;
use App\Http\Controllers\V1\Inertia\Monitor\Car\DocumentMoniteurController;
use App\Http\Controllers\V1\Inertia\Monitor\Car\DocumentProfessionnelController;
use App\Http\Controllers\V1\Inertia\Monitor\Car\CarMoniteurController;
use App\Http\Controllers\V1\Inertia\Monitor\User\UsersController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\MonitorProposalTrainingController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\StudentCancellationController as EleveAnnulationTardiveController;
use App\Http\Controllers\V1\Inertia\System\Zone\LieuxMonitorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// A bearer-style token protects this read-only feed. It intentionally lives
// outside the session-authenticated group because Google, Outlook and Apple
// fetch subscriptions without a logged-in browser session.
Route::get('/calendar/subscription/{token}.ics', [MonitorPlanningCalendarController::class, 'feed'])
    ->where('token', '[a-f0-9]{64}')
    ->middleware('throttle:60,1')
    ->name('calendar.subscription.feed');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'slow'])->group(function () {

    Route::prefix('cancellations')->name('cancellations.')->group(function () {
        Route::controller(MonitorCancellationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });
        Route::controller(EleveAnnulationTardiveController::class)->group(function () {
            Route::post('/', 'store')->name('store');
            Route::prefix('{cancellation}')->group(function () {
                Route::post('/', 'update')->name('update');
            });
        });
    });

    Route::prefix('reservations')->name('reservations.')
        ->controller(ReservationMonitorController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/schedule', 'schedule')->name('schedule');
            Route::get('/events', 'events')->name('events');
            Route::post('/store', 'storeMany')->name('createMany');
            Route::delete('/{reservation}', 'destroy')->name('destroy');
        });

    Route::get('/reservations/export/pdf', [MonitorPlanningPdfController::class, 'download'])
        ->name('reservations.export.pdf');
    Route::get('/reservations/export/ics', [MonitorPlanningCalendarController::class, 'download'])
        ->name('reservations.export.ics');

    Route::prefix('calendar/subscription')->name('calendar.subscription.')->group(function () {
        Route::get('/', [MonitorPlanningCalendarController::class, 'subscription'])->name('show');
        Route::post('/regenerate', [MonitorPlanningCalendarController::class, 'regenerate'])->name('regenerate');
        Route::delete('/', [MonitorPlanningCalendarController::class, 'revoke'])->name('revoke');
    });

    Route::prefix('cancellations')->name('cancellations.')
        ->controller(MonitorCancellationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });
    Route::prefix('proposals')->name('proposals.')
        ->controller(ProposalTrainingController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'count')->name('count');
            Route::post('/create-many', 'storeMany')->name('storeMany');
            Route::prefix('{trainingProposal}')->group(function () {
                Route::post('/', 'update')->name('update');
                Route::delete('/', 'delete')->name('delete');
            });
        });

    // proposals geting
    Route::prefix('proposals')->name('proposals.')
        ->controller(MonitorProposalTrainingController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            // Route::post('/', 'store')->name('store');
            // Route::post('/create-many', 'storeMany')->name('storeMany');
            Route::prefix('{trainingProposal}')->group(function () {
                // Route::post('/', 'update')->name('update');
                Route::delete('/', 'delete')->name('delete');
            });
        });
    // http://127.0.0.1:8000/api/monitor/proposals?upcomming=true&status=1
    Route::prefix('students')->name('students.')
        ->controller(ListStudentByMonitorController::class)->group(function () {
            Route::get('/', 'getAllEleves')->name('index');
            Route::put('/{student}/update', 'updateEleve')->name('update');
        });

    Route::prefix('formation/{student}')->name('formation.')->controller(FormationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/profile-hours', 'profileHours')->name('profile-hours.show');
        Route::get('/profile-exam', 'profileExam')->name('profile-exam.show');
        Route::get('/maneuvers', 'maneuverHistory')->name('maneuvers.history');
        Route::get('/exam-readiness', 'examReadiness')->name('exam-readiness.show');
        Route::put('/exam-readiness', 'updateExamReadiness')->name('exam-readiness.update');
        Route::post('/livret', 'sendBooklet')->name('booklet.send');
        Route::get('/livret/download', 'downloadBooklet')->name('booklet.download');
        Route::get('/initial-evaluation', 'initialEvaluation')->name('initial-evaluation.show');
        Route::put('/initial-evaluation', 'updateInitialEvaluation')->name('initial-evaluation.update');
        Route::get('/initial-evaluation/contract', 'initialEvaluationContract')->name('initial-evaluation.contract');
        Route::get('/initial-evaluation/questions', 'initialEvaluationQuestions')->name('initial-evaluation.questions.show');
        Route::put('/initial-evaluation/questions', 'updateInitialEvaluationQuestions')->name('initial-evaluation.questions.update');
        Route::get('/{reservation}/schema', 'schema')->name('schema.show');
        Route::put('/{reservation}/schema', 'updateSchema')->name('schema.update');
        Route::post('/', 'store')->name('store');
        Route::put('/{reservation}', 'update')->name('update');
        Route::delete('/{reservation}', 'destroy')->name('destroy');
        Route::post('/{reservation}/sign', 'sign')->name('sign');
    });

    Route::prefix('bilans')->name('bilans.')
        ->controller(BilanController::class)->group(function () {
            Route::get('/students', 'students')->name('students');
            Route::get('/{student}', 'show')->name('show');
            Route::put('/{student}', 'update')->name('update');
        });

    Route::get('/progress/{student}', [StudentProgressController::class, 'show'])
        ->name('progress.show');
    Route::put('/progress/{student}/competencies/{competency}', [StudentProgressController::class, 'updateEvaluation'])
        ->name('progress.competencies.update');

    Route::prefix('reviews')->name('reviews.')
        ->controller(RatingMonitorController::class)->group(function () {
            Route::get('/', 'index')->name('index');

            Route::post('/{reservation}', 'store')->name('store');
            Route::put('/{reviewMonitor}', 'update')->name('update');
        });

    Route::prefix('documents')->name('documents.')
        ->group(function () {
            Route::controller(DocumentMoniteurController::class)->group(function () {
                Route::get('/piece-identities', 'pieceIdentites')->name('identities');
                Route::get('/permis-conduit', 'permisConduire')->name('permis');
                Route::get('/diplom-enseignant', 'diplom')->name('diplom');
                Route::post('/', 'storeOrUpdate')->name('store.or.update');
            });
            // http://localhost:8000/api/monitor/documents/diplom-enseignant

            Route::prefix('professionnel')->name('professionnel.')
                ->controller(DocumentProfessionnelController::class)->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'storeOrUpdate')->name('store.or.update');
                    Route::prefix('edit')->name('edit.')->group(function () {
                        Route::get('/autorisation-enseigner', 'editAutorisation')->name('autorisation');
                    });
                });
        });

    Route::prefix('vehicles')->name('vehicles.')
        ->controller(CarMoniteurController::class)
        ->group(function () {
            Route::get('/', 'apiIndex')->name('index');
            Route::post('/', 'apiStore')->name('store');
            Route::put('/{car}', 'apiUpdate')->name('update');
            Route::delete('/{car}', 'apiDestroy')->name('destroy');
        });

    Route::prefix('invoices')
        ->name('invoices.')
        ->controller(InvoiceController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/rapport', 'rapport')->name('rapport');
            Route::get('{billing}', 'hourRapport2')->name('rapport.hours');
        });

    Route::prefix('places')->name('places.')
        ->controller(LieuxMonitorController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
        });

    // Method: GET, PUT, POST, DELETE For User
    Route::prefix('profile')->name('profile.')
        ->controller(UsersController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            // Route::get('/password-change', 'passwordChange')->name('password.change');
            Route::prefix('{monitor}')->group(function () {
                // Route::put('/edit-password', 'updatePassword')->name('password.update');
                // Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
            });
        });
});
