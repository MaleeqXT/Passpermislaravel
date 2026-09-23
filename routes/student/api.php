<?php

use App\Http\Controllers\V1\EndPoint\Monitor\Reservation\MonitorCancellationController;
use App\Http\Controllers\V1\EndPoint\Student\Proposition\ProposalTrainingController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\SaleController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\Type\StripController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\SaleDetailsController;
use App\Http\Controllers\V1\EndPoint\Student\Training\TrainingStudentController;
use App\Http\Controllers\V1\EndPoint\Student\Training\TrainingAdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Inertia\Student\HomePageController;
use App\Http\Controllers\V1\Inertia\Student\User\UserController;
use App\Http\Controllers\V1\Inertia\Student\Sale\StoreOffresController;
use App\Http\Controllers\V1\EndPoint\Student\User\Wallet\WalletController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\HourRequestController;
use App\Http\Controllers\V1\EndPoint\Student\Document\StudentDocumentController;
use App\Http\Controllers\SatisfactionController;
use App\Http\Controllers\V1\EndPoint\Student\Exam\StudentExamController;


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
    Route::get('/exams', [StudentExamController::class, 'index'])->name('student.exams.index');

    Route::prefix('profile')->controller(UserController::class)->group(function () {
        Route::get('/account', 'accountApi');
        Route::post('/account', 'updateAccountPersonal');
        Route::put('/account/preferences', 'updateAccountPreferences');
    });

    Route::prefix('documents')->controller(StudentDocumentController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::delete('/{documentId}', 'destroy')->where('documentId', '.*');
        Route::get('/{documentId}/download', 'download')->where('documentId', '.*');
    });

    Route::prefix('satisfaction')->controller(SatisfactionController::class)->group(function () {
        Route::get('/available', 'available');
        Route::get('/notifications/unread', 'unreadNotification');
        Route::post('/notifications/{notification}/read', 'markNotificationRead');
        Route::get('/{survey}', 'show');
        Route::post('/{survey}/submit', 'submit');
        Route::post('/responses/{response}/google-click', 'googleClick');
    });

        Route::prefix('/dashboard')->name('dashboard.')
        ->controller(HomePageController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });

    Route::prefix('cancellations')->name('cancellations.')
        ->controller(MonitorCancellationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });

    Route::name('strip.')->prefix('strip')
        ->controller(StripController::class)->group(function () {
            Route::post('/', 'store')->name('store');
            Route::post('/success', 'handleSuccess')->name('success');
        });


    Route::name('commandes.')->prefix('commandes')
        ->controller(SaleController::class)->group(function () {
            Route::get('/', 'store')->name('store');
        });

    Route::get('/offers', [StoreOffresController::class, 'apiIndex'])->name('offers.index');
    Route::get('/wallets', [WalletController::class, 'currentStudentWallets'])->name('wallets.index');
    Route::get('/sales', [SaleDetailsController::class, 'current'])->name('sales.index');

    Route::post('/approvels', [HourRequestController::class, 'store'])->name('approvels.store');

    Route::name('reservations.')->prefix('reservations')->group(function () {
        Route::get('/schedule', [TrainingStudentController::class, 'index'])->name('index');
        Route::get('/list', [TrainingStudentController::class, 'get'])->name('get');
        Route::get('/courses', [TrainingStudentController::class, 'courses'])->name('courses');
        Route::post('/book', [TrainingStudentController::class, 'book'])->name('book');
    });




    Route::name('notifications.')->prefix('notifications')->group(function () {
        Route::name('proposals.')->prefix('proposals')
            ->controller(ProposalTrainingController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/count', 'count')->name('count');
                Route::prefix('{trainingProposal}')->group(function () {
                    Route::post('/', 'update')->name('update');
                });
            });
    });
});
