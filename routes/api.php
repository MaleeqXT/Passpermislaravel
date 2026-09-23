<?php

require __DIR__.'/chat.php';

use App\Http\Controllers\Backend\Front\ContactController;
use App\Http\Controllers\Backend\Front\StudentsController;
use App\Http\Controllers\Backend\Front\Offers\OffersController;
use App\Http\Controllers\ReservationCommentController;
use App\Http\Controllers\V1\EndPoint\RdvPermis\RdvPermisController;
use App\Http\Controllers\V1\EndPoint\Student\Info\StudentStatsController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\CartController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\SaleDetailsController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\StudentPaymentSummaryController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\Type\PaypalController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\Type\StripController;
use App\Http\Controllers\V1\EndPoint\Student\User\Wallet\WalletController;
use App\Http\Controllers\V1\EndPoint\System\Media\MediaController;
use App\Http\Controllers\V1\EndPoint\System\Promo\PromoController;
use App\Http\Controllers\V1\EndPoint\System\User\AuthLogController;
use App\Http\Controllers\V1\EndPoint\System\Zone\ZoneController;
use App\Http\Controllers\V1\Inertia\Admin\AdminCancellationController2;
use App\Http\Controllers\V1\Inertia\CPF\CPFPagesController;
use App\Http\Controllers\V1\Inertia\Student\User\UserController as StudentUserController;
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

Route::prefix('reservations/{reservation}/comments')->group(function () {
    Route::get('/', [ReservationCommentController::class, 'index'])->name('reservations.comments.index');
    Route::post('/', [ReservationCommentController::class, 'store'])->name('reservations.comments.store');
    Route::put('{comment}', [ReservationCommentController::class, 'update'])->name('reservations.comments.update');
    Route::delete('{comment}', [ReservationCommentController::class, 'destroy'])->name('reservations.comments.destroy');
});

// payment test Stripe todo @reda
Route::prefix('payment')->name('payment.')
    ->group(function () {
        Route::prefix('stripe')->name('stripe.')->controller(StripController::class)
            ->group(function () {
                Route::post('complete', 'store')->name('store');
                Route::post('success', 'handleSuccess')->name('success');
                Route::post('refund/{sale}', 'refund')->name('refund');
            });
        Route::prefix('paypal')->name('paypal.')
            ->controller(PaypalController::class)
            ->group(function () {
                // Route::prefix('transaction')->name('transaction.')->group(function () {
                Route::get('process', 'processTransaction')->name('process');
                Route::get('success', 'successTransaction')->name('success');
                Route::get('cancel', 'cancelTransaction')->name('cancel');
                // });
            });
    });

Route::prefix('balances')->name('balances.')->controller(WalletController::class)->group(function () {
    Route::prefix('student')->name('student.')->group(function () {
        Route::prefix('{student}')->group(function () {
            Route::get('/', 'getBalanceByStudent')->name('getBalanceByStudent');
            Route::post('/', 'balanceManaging')->name('balanceManaging');
        });
        Route::put('/{wallet}', 'update')->name('updateBalance');
    });
});

Route::prefix('auth')->name('auth.')
    ->controller(AuthLogController::class)
    ->group(function () {
        Route::post('/login', 'login')->name('login');
    });

// Public website contact form: stores rows in contact_us.
Route::post('/contacts', [ContactController::class, 'apiStore']);
Route::post('/public/register/student', [StudentsController::class, 'store']);
Route::get('/public/offers', [OffersController::class, 'apiIndex']);
Route::get('/public/cpf/zones', [CPFPagesController::class, 'publicZones']);
Route::post('/public/cpf/positioning', [CPFPagesController::class, 'storePublicPositioning']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthLogController::class, 'getAuthUser']);
    Route::post('/auth/logout', [AuthLogController::class, 'logout']);
});

// Government OAuth callback must remain server-side and does not use the SPA.
Route::get('/rdvpermis/callback', [RdvPermisController::class, 'callback'])->name('rdvpermis.callback');

Route::middleware(['auth:sanctum', 'role:admin|super-admin|secretary'])->prefix('rdvpermis')->name('rdvpermis.')->group(function () {
    Route::get('/status', [RdvPermisController::class, 'status'])->name('status');
    Route::post('/connect', [RdvPermisController::class, 'connect'])->name('connect');
    Route::get('/current-school', [RdvPermisController::class, 'currentSchool'])->name('current-school');
    Route::get('/employees', [RdvPermisController::class, 'employees'])->name('employees');
});

Route::prefix('pages')->name('pages.')
    ->group(function () {
        Route::prefix('/')
            ->controller(PromoController::class)
            ->group(function () {
                Route::get('/home', 'home')->name('home');
            });
    });

Route::get('/candidates', [ZoneController::class, 'getCandidates'])->name('api.candidates.index');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'slow'])->group(function () {
    Route::get('/students/{student}/contract', [StudentUserController::class, 'showContract'])
        ->name('students.contract.show');
    Route::post('/students/{student}/required-documents', [StudentUserController::class, 'storeRequiredDocument'])
        ->name('students.required-documents.store');
    Route::middleware('role:admin|super-admin|secretary')->get('/admin/contacts', [ContactController::class, 'apiIndex']);
    Route::middleware('role:admin|super-admin|secretary')->prefix('approvels')->controller(AdminCancellationController2::class)->group(function () {
        Route::get('/', 'apiIndex');
        Route::post('/{cancellation}/approve', 'approve');
        Route::post('/{cancellation}/reject', 'reject');
    });

    // Method: GET, PUT, POST, DELETE For Carts
    Route::name('carts.')->prefix('carts')
        ->controller(CartController::class)->group(function () {
            Route::get('/{user?}', 'index')->name('index');
            Route::post('/', 'storeOrUpdate')->name('storeOrUpdate');
            Route::post('/many/{user?}', 'storeOrUpdateMany')->name('storeOrUpdateMany');
            Route::patch('/{offerId}', 'updateCartDetail')->name('updateCartDetail');
            Route::prefix('{cartDetail}')->group(function () {
                Route::delete('/', 'destroy')->name('destroy');
            });
        });
    // /api/stats/students/a1a67cd5-877d-4405-a376-262397fbe857

    Route::prefix('stats')->name('stats.')->controller(StudentStatsController::class)->group(function () {
        Route::prefix('students/{student}')->name('students.')->group(function () {
            Route::get('/', 'index')->name('index');
        });
        // @check this later
        Route::prefix('monitors')->name('monitors.')->group(function () {
            Route::get('/', 'upcomingTraining')->name('upcoming');
        });
    });

    Route::prefix('media')->name('media.')
        ->controller(MediaController::class)->group(function () {
            // Show all or one
            Route::prefix('show')->name('show')
                ->group(function () {
                    Route::get('/by/{media_id}', 'show');
                    Route::get('all', 'showAll')->name('.all');
                });

            // Store many or one
            Route::prefix('store')->name('store')
                ->group(function () {
                    Route::post('/', 'store');
                    Route::post('/many', 'storeMany')->name('.many');
                });

            // Delete many or one
            Route::prefix('destroy')->name('destroy')
                ->group(function () {
                    Route::delete('/by/{storageMedia}', 'destroy');
                    Route::delete('/many', 'destroyMany')->name('.many');
                    Route::delete('/model/{media}', 'deleteMediaModel')->name('.media');
                });
        });

    // Student sales details (includes cart.cartDetails)
    Route::get('students/{student}/sales-with-details', [SaleDetailsController::class, 'index'])->name('students.sales.details');
    Route::get('student/payment-summary', [StudentPaymentSummaryController::class, 'show'])->name('student.payment-summary');
    Route::get('student/payments/{sale}/invoice', [StudentPaymentSummaryController::class, 'download'])->name('student.payment.invoice');
});
