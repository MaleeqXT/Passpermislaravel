<?php

use App\Http\Controllers\V1\EndPoint\Monitor\Invoice\InvoiceController;
use App\Http\Controllers\V1\EndPoint\Monitor\Reservation\ReservationAdminController;
use App\Http\Controllers\V1\EndPoint\Monitor\User\UserMonitorController;
use App\Http\Controllers\V1\EndPoint\Student\Info\AvailableStudentController;
use App\Http\Controllers\V1\EndPoint\Student\Info\CommentStudentController;
use App\Http\Controllers\V1\EndPoint\Student\Info\InfoHourStudentController;
use App\Http\Controllers\V1\EndPoint\Student\Info\CompetencyStudentInfoController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\CartController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\Type\StripController;
use App\Http\Controllers\V1\EndPoint\Student\User\UserStudentController;
use App\Http\Controllers\V1\EndPoint\Student\User\Wallet\WalletController;
use App\Http\Controllers\V1\EndPoint\System\Offre\OffreController;
use App\Http\Controllers\V1\EndPoint\System\User\UserController;
use App\Http\Controllers\V1\EndPoint\System\Zone\ZoneController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\AdminProposalTrainingController;
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

Route::prefix('locations')->name('locations.')
    ->controller(ZoneController::class)->group(function () {
    Route::get('/area', 'getAllZones')->name('area.index');
    Route::prefix('/{zone}')->group(function () {
        Route::get('/zips', 'getAllZips')->name('zips.index');
        Route::get('/places', 'getAllLieux')->name('places.index');
    });
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'slow'])->group(function () {

    Route::prefix('proposals')->name('proposals.')
        ->controller(AdminProposalTrainingController::class)->group(function () {
            Route::get('/', 'proposals')->name('index');
        });
    Route::prefix('reservations')->name('reservations.')
        ->controller(ReservationAdminController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/events', 'events')->name('events');
            Route::prefix('reservation')->name('reservation.')->group(function () {
                Route::post('/store', 'store')->name('store');
                Route::put('/update', 'update')->name('update');
            });
            Route::prefix('{reservation}')->group(function () {
                Route::put('/', 'update')->name('update');
                Route::delete('/', 'destroy')->name('destroy');
                Route::put('/annulation-session', 'CancellationTraining')->name('annulation.session');
            });
        });

    // TODO: balance need to be refactored (we have some duplicated routes in global API)
    Route::prefix('balances')->name('balances.')->controller(WalletController::class)->group(function () {
        Route::prefix('student')->name('student.')->group(function () {
            Route::prefix('{student}')->group(function () {
                Route::get('/', 'getBalanceByStudent')->name('getBalanceByStudent');
                Route::post('/', 'balanceManaging')->name('balanceManaging');
            });
            Route::put('/{wallet}', 'update')->name('updateBalance');
        });
    });



    Route::name('payment.')->prefix('payment')
        ->controller(StripController::class)->group(function () {
            Route::post('/', 'store')->name('store');
        });
    // shop routes
    Route::prefix('shop')->name('shop.')->controller(ZoneController::class)->group(function () {

        Route::prefix('offer')->controller(OffreController::class)
            ->name('offers.')->group(function () {
                Route::get('/', 'getAllProducts')->name('index');
            });
        Route::prefix('cart')->controller(CartController::class)
            ->name('cart.')->group(function () {
                Route::get('/{user}', 'getCart')->name('getCart');
                Route::post('/{user}', 'storeOrUpdateMany')->name('storeOrUpdateMany');
            });
    });

    Route::prefix('students')->controller(UserStudentController::class)
        ->name('students.')->group(function () {
            Route::prefix('/')->group(function () {
                Route::get('all', 'getAllEleves')->name('all');
            });
        });

    // Route::prefix('operators')->name('operators.')->controller(UserAdminController::class)
    //     ->group(function () {
    //         Route::prefix('/')->group(function () {
    //             Route::get('all', 'getAllOperators')->name('all');
    //         });
    //     });

    Route::prefix('user')->controller(UserController::class)
        ->name('user.')->group(function () {
            Route::prefix('/{user}')->group(function () {
                Route::put('', 'archiveUser')->name('archiveUser');
            });
        });

    // Route::prefix('available-calls')->name('available-calls.')
    //     ->controller(CallController::class)->group(function () {
    //         Route::get('/', 'index')->name('index');
    //         Route::post('/', 'store')->name('store');
    //         Route::prefix('{eleveAvailableCall}')->group(function () {
    //             Route::put('/', 'update')->name('update');
    //             Route::delete('/', 'destroy')->name('destroy');
    //         });
    //     });

    Route::prefix('availables')->name('availables.')
        ->controller(AvailableStudentController::class)->group(function () {
            Route::prefix('{student}')->group(function () {
                Route::get('/', 'index')->name('all');
                Route::get('/planning', 'getAllReservationsGroupedByWeek')->name('getAllReservationsGroupedByWeek');
            });
            Route::post('/', 'store')->name('store');
            Route::prefix('{studentAvailability}')->group(function () {
                Route::delete('/', 'destroy')->name('destroy');
            });
        });

    Route::prefix('comments')->controller(CommentStudentController::class)
        ->name('comments.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::prefix('/{studentNote}')->group(function () {
                Route::post('/', 'update')->name('update');
                Route::delete('/', 'destroy')->name('destroy');
            });
        });

    Route::prefix('competences')->controller(CompetencyStudentInfoController::class)
        ->name('competences.')->group(function () {
            Route::get('/{student}', 'index')->name('index');
        });


    Route::prefix('resume-hours')->controller(InfoHourStudentController::class)
        ->name('resume-hours.')->group(function () {
            Route::get('/{student}', 'index')->name('index');
        });

    Route::prefix('monitors')->name('monitors.')
        ->group(function () {
            Route::prefix('/')
                ->controller(UserMonitorController::class)
                ->group(function () {
                    Route::get('/all', 'getAllMoniteurs')->name('all');
                });

            Route::prefix('invoice/{monitor}')
                ->name('invoice.')
                ->controller(InvoiceController::class)
                ->group(function () {
                    Route::get('{billing}', 'getResumeHours')->name('rapport.hours');
                });
        });
});
