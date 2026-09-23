<?php

use App\Http\Controllers\V1\EndPoint\Monitor\Invoice\InvoiceController;
use App\Http\Controllers\V1\EndPoint\Monitor\Rating\RatingMonitorController;
use App\Http\Controllers\V1\EndPoint\Monitor\Reservation\MonitorCancellationController;
use App\Http\Controllers\V1\EndPoint\Monitor\Reservation\ReservationMonitorController;
use App\Http\Controllers\V1\EndPoint\Student\Info\ListStudentByMonitorController;
use App\Http\Controllers\V1\EndPoint\Student\Proposition\ProposalTrainingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Inertia\Monitor\User\UsersController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\StudentCancellationController as EleveAnnulationTardiveController;

use App\Http\Controllers\V1\Inertia\System\Zone\LieuxMonitorController;

use App\Http\Controllers\V1\Inertia\Monitor\Car\DocumentMoniteurController;
use App\Http\Controllers\V1\Inertia\Monitor\Car\DocumentProfessionnelController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\MonitorProposalTrainingController;




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

        //proposals geting
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

    Route::prefix('invoices')
        ->name('invoices.')
        ->controller(InvoiceController::class)
        ->group(function () {
            // Route::get('/invoice/rapport', 'rapport')->name('rapport');
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
