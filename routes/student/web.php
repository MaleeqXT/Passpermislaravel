<?php

use App\Http\Controllers\Backend\Eleve\ContractFormationController;
use App\Http\Controllers\Backend\Eleve\NephController;
use App\Http\Controllers\V1\Inertia\Student\Competence\CompetenceStudentController;
use App\Http\Controllers\V1\Inertia\Student\Cpf\CpfStudentController;
use App\Http\Controllers\V1\Inertia\Student\Evaluation\DocumentEvaluationController;
use App\Http\Controllers\V1\Inertia\Student\HomePageController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\StudentCancellationController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\HourRequestController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\TrainingController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\TrainingNotificationsController;
use App\Http\Controllers\V1\Inertia\Student\Sale\StoreOffresController;
use App\Http\Controllers\V1\Inertia\Student\Sale\StudentCartController;
use App\Http\Controllers\V1\Inertia\Student\Sale\StudentSaleController;
use App\Http\Controllers\V1\Inertia\Student\User\UserController;
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


Route::middleware(['auth:sanctum,web', config('jetstream.auth_session'), 'verified', 'role:student', 'slow'])->group(function () {

    // Route::prefix('/dashboard')->name('dashboard.')
    //     ->controller(HomePageController::class)->group(function () {
    //         Route::get('/', 'index')->name('index');
    //     });

    // Method: GET, PUT, POST, DELETE For User
    Route::prefix('profile')->name('profile.')
        ->controller(UserController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            // Route::get('/password-change', 'passwordChange')->name('password.change');
            // Route::post('/', 'store')->name('store');
            // Route::get('/create', 'create')->name('create');
            Route::prefix('{student}')->group(function () {
                // Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
                // Route::put('/edit-password', 'updatePassword')->name('password.update');
                Route::delete('/', 'destroy')->name('destroy');
            });
        });


    Route::name('settings.')->prefix('settings')->group(function () {
        Route::name('contrat-de-formation.')->prefix('contrat-de-formation')->controller(ContractFormationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });
        Route::name('neph.')->prefix('neph')->controller(NephController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::put('/', 'update')->name('update');
        });
    });

    // Method: GET, PUT, POST, DELETE For Carts
    Route::prefix('reservations')->name('reservations.')
        ->controller(TrainingController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
        });

    Route::name('shop.')->prefix('shop')
        ->controller(StoreOffresController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });

    // TODO: deprecated
    Route::name('carts.')->prefix('carts')
        ->controller(StudentCartController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });

    Route::name('cancellations.')->prefix('cancellations')
        ->controller(StudentCancellationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::prefix('{cancellation}')->group(function () {
                Route::post('/', 'update')->name('update');
            });
        });

    // route for student hour adjustment requests (add/minus hours) sent to admin
    Route::name('approvels.')->prefix('approvels')
        ->controller(HourRequestController::class)->group(function () {
            Route::post('/', 'store')->name('store');
        });

    Route::name('notifications.')->prefix('notifications')
        ->controller(TrainingNotificationsController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });

    // Method: GET, PUT, POST, DELETE For Order
    Route::prefix('commandes')->name('commandes.')
        ->controller(StudentSaleController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::prefix('{sale}')->group(function () {
                Route::get('/', 'show')->name('show');
            });
        });

    Route::prefix('competences')->name('competences.')
        ->controller(CompetenceStudentController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });

    Route::name('cpf.')->prefix('cpf')
        ->controller(CpfStudentController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::prefix('{cpfEleve}')->group(function () {
                Route::get('/', 'view')->name('view');
                Route::post('/', 'store')->name('store');
            });
        });

    Route::controller(DocumentEvaluationController::class)->group(function () {
        // Route::get('/', 'index')->name('index');
        Route::get('/pdf/{documentEvaluation}', 'evaluationsPdf')->name('evaluations.pdf');
        Route::get('/contract/pdf/{reservation}', 'contractPdf')->name('contract.pdf');
        // Route::get('/chose', 'choseDissection')->name('choseDissection');
    });
});
