<?php

use App\Http\Controllers\V1\EndPoint\Student\Sale\Type\StripController;
use App\Http\Controllers\V1\EndPoint\System\Media\ImageController;
use App\Http\Controllers\V1\Inertia\CPF\CPFPagesController;
use App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Invoice\InvoiceAdminController;
use App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Invoice\InvoiceMonitorController;
use App\Http\Controllers\V1\Inertia\Student\Sale\StudentSaleController;
use App\Http\Controllers\V1\Inertia\Student\User\StudentUserController;
use App\Http\Controllers\V1\Inertia\Secretary\SecretaryPageController;
use App\Http\Controllers\TrainingProposalController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ReservationRequestController;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ReservationCommentController;
use App\Http\Controllers\V1\Inertia\Monitor\MonitorExportController;
use App\Http\Controllers\V1\Inertia\Student\StudentExportController;
use App\Http\Controllers\SecretaryController;
use App\Http\Controllers\SecretaryLoginController;
use App\Http\Controllers\Backend\Eleve\ContractFormationController;

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
// routes/web.php

Route::get('/vehicle-files/{path}', function (string $path) {
    abort_unless(str_starts_with($path, 'vehicles/') && Storage::disk('public')->exists($path), 404);
    return response()->file(Storage::disk('public')->path($path));
})->where('path', '.*')->name('vehicle-files.show');

Route::get('/clear-all', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('optimize');

    return " All caches cleared & optimized!";
});


Route::delete('/admin/users/students/{student}', [StudentUserController::class, 'destroy'])
    ->name('admin.users.students.destroy');




Route::prefix('secretaries')->name('secretaries.')->group(function () {
        Route::get('/index', [SecretaryController::class, 'index'])->name('index');
    Route::get('/create', [SecretaryController::class, 'create'])->name('create'); // form page
    Route::post('/', [SecretaryController::class, 'store'])->name('store');  // save new secretary

    Route::put('/{secretary}', [SecretaryController::class, 'update'])->name('update');

});


Route::get('/secretaries/{secretary}/edit', [SecretaryController::class, 'edit'])->name('secretaries.edit');


Route::get('admin/users/monitors/{monitor}/export/excel', [MonitorExportController::class, 'exportExcel'])
    ->name('admin.users.monitors.export.excel');

// Students exports
Route::get('admin/users/students/export-all', [StudentExportController::class, 'exportAll'])
    ->name('admin.users.students.export.all');

Route::get('admin/users/students/{student}/export', [StudentExportController::class, 'exportStudent'])
    ->name('admin.users.students.export');


// Route::prefix('reservations/{reservation}/comments')->group(function () {
//     Route::get('/', [ReservationCommentController::class, 'index'])->name('reservations.comments.index');
//     Route::post('/', [ReservationCommentController::class, 'store'])->name('reservations.comments.store');
//     Route::patch('{comment}', [ReservationCommentController::class, 'update'])->name('reservations.comments.update');
//     Route::delete('{comment}', [ReservationCommentController::class, 'destroy'])->name('reservations.comments.destroy');
// });


Route::get(uri: '/secretary/dashboard', action: [SecretaryLoginController::class, 'index'])->name('login.secretary');
Route::get('/secretary/dashboard', [SecretaryLoginController::class, 'index'])
    ->name('secretary.dashboard.index');



Route::get('/secretary/profile', [SecretaryLoginController::class, 'profile'])
    ->middleware('auth')
    ->name('secretary.profile');

Route::patch('/reservation-requests/{id}', [ReservationRequestController::class, 'update'])
    ->name('reservation-requests.update');

Route::get('/monitor/requests', action: [ReservationRequestController::class, 'requests'])->name('monitor');


Route::post('/api/reservation-requests', [ReservationRequestController::class, 'store'])->name('reservation-requests.store');

Route::get('/api/training-proposals', [TrainingProposalController::class, 'index']);
// payment test Stripe todo @reda
Route::get('/payments', [StripController::class, 'index'])->name('payment');

// Contract preview from the staff/student React dashboard. The controller checks
// that only staff may request a contract for another student.
Route::get('/student/settings/contrat-de-formation/{student}', [ContractFormationController::class, 'staff'])
    ->middleware(['auth:sanctum,web', config('jetstream.auth_session'), 'verified', 'slow'])
    ->name('student.contract.staff');

//Route::get('/', function () {
//
//
//    return Inertia::render('Welcome', [
//        'canLogin' => Route::has('login'),
//        'canRegister' => Route::has('register'),
//        'laravelVersion' => Application::VERSION,
//        'phpVersion' => PHP_VERSION,
//    ]);
//});

Route::get('/admin', function () {
    if (auth()?->user()?->hasRole('admin')) {
        return redirect()->route('admin.dashboard.index');
    } elseif (auth()?->user()?->hasRole('monitor')) {
        return redirect()->route('monitor.dashboard.index');
    } elseif (auth()?->user()?->hasRole('secretary')) {
        return redirect()->route('secretary.dashboard.index'); // 👉 secretary dashboard
    } elseif (auth()?->user()?->hasRole('student')) {
        return redirect()->route('student.dashboard.index');
    } else {
        return redirect()->route('login');
    }
})->name('do');


// Route::prefix('admin')->group(function () {
//     Route::get('/secretary', [SecretaryPageController::class, 'index'])
//         ->name('secretary.dashboard.index');
// });


// Route::get('/', function () {
//     // Bugsnag::notifyException(new RuntimeException("Test error"));
//     return redirect()->route('do');
// })->name('/');


Route::post('/image-upload', [ImageController::class, 'storeImage'])->name('image.upload');

Route::get('/download/monitor/invoices/{billing}', [InvoiceMonitorController::class, 'download'])->name('m.invoice');
Route::get('/download/student/invoices/{sale}', [StudentSaleController::class, 'download'])->name('s.invoice');

Route::prefix('impersonate')->name('impersonate.')
    ->controller(StudentUserController::class)->group(function () {
        Route::prefix('user')->group(function () {
            Route::get('/stop', 'impersonateStop')->name('stop');
            Route::get('/start/{user}', 'impersonateStart')->name('start');
        });
    });

    
