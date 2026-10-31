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
use App\Http\Controllers\V1\EndPoint\Admin\StudentDashboardPreviewController;
use App\Http\Controllers\V1\EndPoint\Student\StudentProgressController;
use App\Http\Controllers\V1\EndPoint\Student\StudentFormationController;
use App\Http\Controllers\V1\EndPoint\Bilan\BilanController;
use App\Http\Controllers\Theory\TheoryStudentController;


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

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified-or-student-preview', 'slow'])->group(function () {
    Route::delete('/preview-session', [StudentDashboardPreviewController::class, 'stop'])
        ->name('student.preview-session.stop');
    Route::prefix('theory')->controller(TheoryStudentController::class)->group(function () {
        Route::get('/access', 'access');
        Route::get('/dashboard', 'dashboard');
        Route::post('/attempts', 'start');
        Route::get('/attempts/{attempt}', 'show');
        Route::put('/attempts/{attempt}/answers/{answer}', 'saveAnswer');
        Route::post('/attempts/{attempt}/answers/{answer}/validate', 'validateTrainingAnswer');
        Route::post('/attempts/{attempt}/submit', 'submit');
    });

    Route::get('/exams', [StudentExamController::class, 'index'])->name('student.exams.index');
    Route::get('/progress', [StudentProgressController::class, 'show'])->name('student.progress.show');
    Route::get('/progress/lessons/{reservation}', [StudentProgressController::class, 'lessonDetail'])->name('student.progress.lessons.show');
    Route::get('/bilans', [BilanController::class, 'showForStudent'])->name('student.bilans.show');
    Route::prefix('formation')->controller(StudentFormationController::class)->group(function () {
        Route::get('/', 'index')->name('student.formation.index');
        Route::get('/lessons/{reservation}/schema', 'schema')->name('student.formation.schema');
        Route::put('/lessons/{reservation}/schema', 'updateSchema')->name('student.formation.schema.update');
        Route::post('/booklet', 'sendBooklet')->middleware('throttle:3,1')->name('student.formation.booklet.send');
        Route::get('/booklet/download', 'downloadBooklet')->name('student.formation.booklet.download');
    });
    Route::put('/progress/competencies/{competency}/comment', [StudentProgressController::class, 'updateStudentComment'])
        ->name('student.progress.competencies.comment.update');

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
        Route::get('/pending', 'pending');
        Route::get('/stage/{stage}', 'stage');
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
