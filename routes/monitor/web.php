<?php


use App\Http\Controllers\V1\Inertia\Monitor\Car\CarMoniteurController;
use App\Http\Controllers\V1\Inertia\Monitor\Car\DocumentMoniteurController;
use App\Http\Controllers\V1\Inertia\Monitor\Car\DocumentProfessionnelController;
use App\Http\Controllers\V1\Inertia\Monitor\Evaluation\DocumentEvaluationController;
use App\Http\Controllers\V1\Inertia\Monitor\HomePageController;
use App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\StudentListController;
use App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Invoice\InvoiceMonitorController;
use App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Reservation\ReservationMonitorController;
use App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Reservation\ReservationParamsController;
use App\Http\Controllers\V1\Inertia\Monitor\User\MonitorPrivateAccountController;
use App\Http\Controllers\V1\Inertia\Monitor\User\UsersController;
use App\Http\Controllers\V1\Inertia\Student\Competence\CompetenceMonitorController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\MonitorCancellationController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\MonitorProposalTrainingController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\MonitorTrainingController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\RatingMonitorTrainingController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\StudentCancellationController as EleveAnnulationTardiveController;
use App\Http\Controllers\V1\Inertia\System\Zone\LieuxMonitorController;
use App\Http\Controllers\V1\Inertia\System\Zone\ZoneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// routes/monitor/web.php




Route::middleware(['auth:sanctum,web', config('jetstream.auth_session'), 'verified', 'role:monitor', 'slow'])->group(function () {
    // Method: GET, PUT, POST, DELETE For User
    // Route::prefix('users')->name('users.')
    //     ->controller(UsersController::class)->group(function () {
    //         Route::post('/', 'store')->name('store');
    //         Route::get('/create', 'create')->name('create');
    //     });

    Route::name('dashboard.')->controller(HomePageController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('index');
        Route::get('/competences/{student}', 'competences')->name('competences');
    });

    Route::prefix('reservations')->name('reservations.')->group(function () {
        Route::controller(ReservationMonitorController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/create', 'create')->name('create');
            Route::get('/create-many', 'storeMany')->name('create.many');
            Route::delete('/{reservation}', 'destroy')->name('destroy');

            Route::prefix('settings')->name('settings.')
                ->controller(ReservationParamsController::class)->group(function () {
                    Route::post('/', 'storeOrUpdate')->name('store.or.update');
                });
        });
        Route::prefix('student')->name('student.')->controller(MonitorTrainingController::class)->group(function () {
            Route::get('/{student}', 'index')->name('index');
        });
    });

    // Route::prefix('reservations')->name('reservations.')->controller(MonitorTrainingController::class)->group(function () {
    //     Route::get('/{student}', 'index')->name('index');
    // });

    // // Method: GET, PUT, POST, DELETE For User
    // Route::prefix('profile')->name('profile.')
    //     ->controller(UsersController::class)->group(function () {
    //         Route::get('/', 'index')->name('index');
    //         // Route::get('/password-change', 'passwordChange')->name('password.change');
    //         Route::prefix('{monitor}')->group(function () {
    //             // Route::put('/edit-password', 'updatePassword')->name('password.update');
    //             // Route::get('/', 'edit')->name('edit');
    //             Route::put('/', 'update')->name('update');
    //         });
    //     });
    Route::prefix('students')->name('students.')
        ->controller(StudentListController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });
    // Method: GET, PUT, POST, DELETE For Zones
    Route::prefix('zones')->name('zones.')
        ->controller(ZoneController::class)->group(function () {
            Route::prefix('{monitor}')->group(function () {
                Route::post('/attach', 'attachLieuToMoniteur')->name('attachLieuToMoniteur');
                Route::post('/detach', 'detachLieuToMoniteur')->name('detachLieuToMoniteur');
            });
        });


    // Route::prefix('proposals')->name('proposals.')
    //     ->controller(MonitorProposalTrainingController::class)->group(function () {
    //         Route::get('/', 'index')->name('index');
    //         // Route::post('/', 'store')->name('store');
    //         // Route::post('/create-many', 'storeMany')->name('storeMany');
    //         Route::prefix('{trainingProposal}')->group(function () {
    //             // Route::post('/', 'update')->name('update');
    //             Route::delete('/', 'delete')->name('delete');
    //         });
    //     });

    Route::prefix('competences')->name('competences.')
        ->controller(CompetenceMonitorController::class)->group(function () {
            Route::get('/{student}', 'index')->name('index');
            Route::post('/{competency}', 'storeOrUpdate')->name('storeOrUpdate');
        });

    Route::prefix('reviews')->name('reviews.')
        ->controller(RatingMonitorTrainingController::class)->group(function () {
            Route::get('/{student}', 'index')->name('index');
            Route::get('/show/{reviewMonitor}', 'show')->name('show');
        });

    // Route::prefix('cancellations')->name('cancellations.')->group(function () {
    //     Route::controller(MonitorCancellationController::class)->group(function () {
    //         Route::get('/', 'index')->name('index');
    //     });
    //     Route::controller(EleveAnnulationTardiveController::class)->group(function () {
    //         Route::post('/', 'store')->name('store');
    //         Route::prefix('{cancellation}')->group(function () {
    //             Route::post('/', 'update')->name('update');
    //         });
    //     });
    // });


    // Route::prefix('places')->name('places.')
    //     ->controller(LieuxMonitorController::class)->group(function () {
    //         Route::get('/', 'index')->name('index');
    //         Route::post('/', 'store')->name('store');
    //     });

    Route::prefix('cars')->name('cars.')
        ->controller(CarMoniteurController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/create', 'create')->name('create');
            Route::prefix('{car}')->group(function () {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
                Route::delete('/', 'destroy')->name('delete');
            });
        });

    // Route::prefix('documents')->name('documents.')
    //     ->group(function () {
    //         Route::controller(DocumentMoniteurController::class)->group(function () {
    //             Route::get('/piece-identities', 'pieceIdentites')->name('identities');
    //             Route::get('/permis-conduit', 'permisConduire')->name('permis');
    //             Route::get('/diplome-enseignant', 'diplom')->name('diplom');
    //             Route::post('/', 'storeOrUpdate')->name('store.or.update');
    //         });
    //         Route::prefix('professionnel')->name('professionnel.')
    //             ->controller(DocumentProfessionnelController::class)->group(function () {
    //                 Route::get('/', 'index')->name('index');
    //                 Route::post('/', 'storeOrUpdate')->name('store.or.update');
    //                 Route::prefix('edit')->name('edit.')->group(function () {
    //                     Route::get('/autorisation-enseigner', 'editAutorisation')->name('autorisation');
    //                 });
    //             });
    //     });
    Route::prefix('accounts')->name('accounts.')
        ->controller(MonitorPrivateAccountController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'storeOrUpdate')->name('store.or.update');
        });

    Route::prefix('invoices')->name('invoices.')
        ->controller(InvoiceMonitorController::class)->group(function () {
            Route::get('/menu', 'menu')->name('menu');
            Route::get('/historique', 'historique')->name('historique');
            Route::get('/', 'index')->name('index');
            Route::get('/{billing}', 'view')->name('view');
        });

    Route::name('evaluations.')->prefix('evaluations')
        ->controller(DocumentEvaluationController::class)->group(function () {
            // Route::get('/', 'index')->name('index');
            Route::post('/{student}', 'store')->name('store');
        });
});
