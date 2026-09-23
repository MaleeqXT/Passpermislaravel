<?php

use App\Http\Controllers\Backend\Admin\Exams\ExamListController;
use App\Http\Controllers\V1\Inertia\System\User\UserController;
use App\Http\Controllers\V1\Inertia\Admin\HomePageController;
use App\Http\Controllers\V1\Inertia\CPF\CPFPagesController;
use App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Invoice\InvoiceAdminController;
use App\Http\Controllers\V1\Inertia\Monitor\Infrastructure\Reservation\ReservationAdminController;
use App\Http\Controllers\V1\Inertia\Monitor\User\Info\InformationMonitorController;
use App\Http\Controllers\V1\Inertia\Monitor\User\Info\UserMonitorController;
use App\Http\Controllers\V1\Inertia\Student\Competence\CompetenceAdminController;
use App\Http\Controllers\V1\Inertia\Student\Competence\MainCompetenceAdminController;
use App\Http\Controllers\V1\Inertia\Student\Cpf\CpfController;
use App\Http\Controllers\V1\Inertia\Student\Evaluation\DocumentEvaluationController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\AdminCancellationController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\AdminProposalTrainingController;
use App\Http\Controllers\V1\Inertia\Student\Sale\AdminCartController;
use App\Http\Controllers\SecretaryLoginController;
use App\Http\Controllers\V1\Inertia\Student\Sale\AdminSaleController;
use App\Http\Controllers\V1\Inertia\Student\User\StudentUserController;
use App\Http\Controllers\V1\Inertia\System\AboutUs\AboutUsController;
use App\Http\Controllers\V1\Inertia\System\Promo\OffresController;
use App\Http\Controllers\V1\Inertia\System\Promo\PromoController;
use App\Http\Controllers\V1\Inertia\System\User\AdminUserController;
use App\Http\Controllers\V1\Inertia\System\Zone\LieuxController;
use App\Http\Controllers\V1\Inertia\System\Zone\ZipController;
use App\Http\Controllers\V1\Inertia\System\Zone\ZoneController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\V1\Inertia\Admin\AdminCancellationController2;


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



// NOTE: this file previously contained many admin controllers and routes copied from admin/web.php.
// To avoid exposing admin routes to users with the 'secretary' role, the big group should be
// accessible only by admins. Change middleware to 'role:admin' to prevent accidental exposure.
Route::middleware(['auth:sanctum,web', 'role:secretary', 'slow'])->name('secretary.')->group(function () {

    Route::prefix('/approvels')->name('admin.approvels.')->group(function () {
    // List page
    Route::get('/', [AdminCancellationController2::class, 'index'])->name('index');

    // Approve
    Route::post('/{cancellation}/approve', [AdminCancellationController2::class, 'approve'])->name('approve');

    // Reject
    Route::post('/{cancellation}/reject', [AdminCancellationController2::class, 'reject'])->name('reject');
});

Route::get(uri: '/secretary/dashboard', action: [SecretaryLoginController::class, 'index'])->name('login.secretary');
Route::get('/secretary/dashboard', [SecretaryLoginController::class, 'index'])
    ->name('secretary.dashboard.index');

// Secretary-only routes (keep these separate so secretaries don't get admin routes)
Route::middleware(['auth:sanctum,web', 'role:secretary'])->group(function () {
    Route::get('/secretary/profile', [SecretaryLoginController::class, 'profile'])->name('secretary.profile');
});
  
 

    // ========================== Reservations ==========================
    Route::prefix('reservations')->name('reservations.')
        ->controller(ReservationAdminController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::prefix('{reservation}')->group(function () {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
                Route::delete('/', 'destroy')->name('destroy');
            });
        });

    // ========================== cancellations ==========================
    Route::name('cancellations.')->prefix('cancellations')
        ->controller(AdminCancellationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::prefix('{cancellation}')->group(function () {
                Route::put('/', 'update')->name('update');
            });
        });


    // ========================== CPF Management ==========================
    Route::prefix('cpf')->name('cpf.')
        ->controller(CpfController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::prefix('{cpfEleve}')->group(function () {
                Route::put('/', 'update')->name('update');
                Route::delete('/', 'destroy')->name('destroy');
            });
        });

    // ========================== Offers ==========================
    Route::prefix('offers')->name('offers.')
        ->controller(OffresController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/create', 'create')->name('create');
            Route::prefix('{offer}')->group(function () {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
                Route::put('/status', 'updateStatus')->name('updateStatus');
                Route::delete('/', 'destroy')->name('destroy');
            });
        });

    // ========================== Locations ==========================
    Route::prefix('locations')->name('locations.')->group(function () {
        // ----- Areas -----
        Route::prefix('area')->name('area.')
            ->controller(ZoneController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::prefix('{zone}')->group(function () {
                    Route::put('/', 'update')->name('update');
                    Route::delete('/', 'destroy')->name('destroy');
                });
                Route::prefix('{student}')->group(function () {
                    Route::post('/attach', 'attachZoneToEleve')->name('attach');
                    Route::post('/detach', 'detachZoneToEleve')->name('detach');
                });
            });

        // ----- Places -----
        Route::prefix('places')->name('places.')
            ->controller(LieuxController::class)->group(function () {
                Route::prefix('{zone}')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'store')->name('store');
                });
                Route::prefix('{lieu}')->group(function () {
                    Route::put('/', 'update')->name('update');
                    Route::delete('/', 'destroy')->name('destroy');
                });
            });

        // ----- Zips -----
        Route::prefix('zips')->name('zips.')
            ->controller(ZipController::class)->group(function () {
                Route::prefix('{zone}')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'store')->name('store');
                    Route::get('/create', 'create')->name('create');
                });
                Route::prefix('{zip}')->group(function () {
                    Route::get('/', 'edit')->name('edit');
                    Route::put('/', 'update')->name('update');
                    Route::delete('/', 'destroy')->name('destroy');
                });
            });
    });

    // ========================== Users Management ==========================
    Route::prefix('users')->name('users.')->group(function () {
        Route::prefix('{user}')->controller(UserController::class)->group(function () {
            Route::put('', 'archiveUser')->name('archive');
        });

        // ----- Admin Users -----
        Route::prefix('admins')->name('admins.')
            ->controller(AdminUserController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::prefix('{user}')->group(function () {
                    Route::get('/', 'edit')->name('edit');
                    Route::put('/', 'update')->name('update');
                    Route::delete('/', 'destroy')->name('destroy');
                });
            });

        // ----- Monitors (accessible to secretary) -----
        Route::prefix('monitors')->name('monitors.')
            ->controller(UserMonitorController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::prefix('{monitor}')->group(function () {
                    Route::get('/factures', 'invoices')->name('invoices');
                    Route::get('/edit', 'edit')->name('edit');
                    Route::put('/', 'update')->name('update');
                    Route::delete('/', 'destroy')->name('destroy');
                });
                Route::prefix('{monitor}')->controller(InformationMonitorController::class)->group(function () {
                    Route::get('/documents', 'documents')->name('documents');
                    Route::put('/documents', 'documentsUpdate')->name('documents.update');
                });
            });

        // ----- Students (accessible to secretary) -----
        Route::prefix('students')->name('students.')
            ->controller(StudentUserController::class)->group(function () {
                Route::get('/create', 'create')->name('create');
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::prefix('{student}')->group(function () {
                    Route::get('/edit', 'edit')->name('edit');
                    Route::get('/zones', 'getBalanceAndZones')->name('get.balance.locations');
                    Route ::get('/livret', 'getCompetences')->name('get.competences');
                    Route::get('/cpf', 'getCpf')->name('get.cpf');
                    Route::get('/cart', 'getCart')->name('get.cart');
                    Route::get('/docs', 'docs')->name('docs');
                    Route::put('/', 'update')->name('update');
                    Route::post('/', 'archiveEleve')->name('archive');
                });
            });
    });

    // ========================== Exams ==========================
    Route::prefix('exams')->name('exams.')->controller(ExamListController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{examenEleve}', 'edit')->name('edit');
        Route::post('/{examenEleve}', 'update')->name('update');
    });

    // ========================== Carts ==========================
    Route::prefix('carts')->name('carts.')
        ->controller(AdminCartController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::prefix('{cart}')->group(function () {
                Route::get('/', 'show')->name('show');
                Route::delete('/', 'destroy')->name('destroy');
            });
            Route::prefix('item/{cartDetail}')->group(function () {
                Route::delete('/', 'destroyItem')->name('destroy.item');
            });
        });

    // ========================== Commandes ==========================
    Route::prefix('commandes')->name('commandes.')
        ->controller(AdminSaleController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::prefix('{sale}')->group(function () {
                Route::get('/', 'show')->name('show');
            });
        });
    // ========================== Competences ==========================
    Route::prefix('competences')->name('competences.')->group(function () {
        // Main Competences
        Route::prefix('group')->name('group.')
            ->controller(MainCompetenceAdminController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::prefix('{mainCompetency}')->group(function () {
                    Route::put('/', 'update')->name('update');
                    Route::delete('/', 'destroy')->name('destroy');
                });
            });

        // Sub Competences
        Route::prefix('sub')->name('sub.')
            ->controller(CompetenceAdminController::class)->group(function () {
                Route::prefix('{mainCompetency}')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'store')->name('store');
                });
                Route::prefix('{competency}')->group(function () {
                    Route::put('/', 'update')->name('update');
                    Route::delete('/', 'destroy')->name('destroy');
                });
            });
    });

    // ========================== Proposals ==========================
    Route::prefix('proposals')->name('proposals.')
        ->controller(AdminProposalTrainingController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::prefix('{trainingProposal}')->group(function () {
                Route::post('/', 'update')->name('update');
            });
        });

    Route::prefix('invoices')->name('invoices.')
        ->controller(InvoiceAdminController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::prefix('{billing}')->group(function () {
                Route::get('/', 'show')->name('show');
                Route::put('/', 'update')->name('update');
            });
        });
    Route::prefix('pages')->name('pages.')->controller(PromoController::class)
        ->group(function () {
            Route::prefix('promo')->name('promo.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::prefix('{promo}')->group(function () {
                        Route::get('/', 'edit')->name('edit');
                        Route::post('/', 'update')->name('update');
                        Route::delete('/', 'delete')->name('delete');
                    });
                });
            Route::prefix('home')->name('home.')
                ->group(function () {
                    Route::get('/', 'homeCustomize')->name('index');
                    Route::prefix('{page}')->group(function () {
                        Route::post('/', 'update')->name('update');
                    });
                });
            Route::prefix('code')->name('code.')
                ->group(function () {
                    Route::get('/', 'codeCustomize')->name('index');
                    Route::prefix('{page}')->group(function () {
                        Route::post('/', 'update')->name('update');
                    });
                });
            Route::post('/generalUpdate', 'generalUpdate')->name('general.update');
        });

    // ========================== Contact Us ==========================
    Route::prefix('contact')->name('contact.')
        ->controller(AboutUsController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::prefix('{contactUs}')->group(function () {
                Route::put('/', 'update')->name('update');
            });
        });
    Route::controller(DocumentEvaluationController::class)->group(function () {
        Route::get('/pdf/{documentEvaluation}', 'evaluationsPdf')->name('evaluations.pdf');
        Route::get('/contract/pdf/{reservation}', 'contractPdf')->name('contract.pdf');
    });

    Route::name('forms-cpf.')->prefix('forms-cpf')->controller(CPFPagesController::class)->group(function () {
        Route::get('/', 'indexAdmin')->name('index');
        Route::put('/{CPFDocumentInfo}', 'update')->name('update');
        Route::name('test.positionnement.')->prefix('test-positionnement')->group(function () {
            Route::get('/{CPFDocumentInfo}', 'storeTestPosPdf')->name('pdf');
        });
        Route::name('contact.formation.')->prefix('contact-formation/{CPFDocumentInfo}')->group(function () {
            Route::get('/pdf', 'contactFormationPdf')->name('pdf');
        });

        Route::name('attestation.honneur.')->prefix('attestation-honneur/{CPFDocumentInfo}')->group(function () {
            Route::get('/pdf', 'attestationHonneurPdf')->name('pdf');
        });
        Route::name('reservations.')->prefix('reservations/{CPFDocumentInfo}')->group(function () {
            Route::get('/pdf', 'reservationsPdf')->name('pdf');
        });
        Route::name('attestation-fin-formation.')->prefix('attestation-fin-formation/{CPFDocumentInfo}')->group(function () {
            Route::get('/pdf', 'affPdf')->name('pdf');
        });

        Route::post('/{CPFDocumentInfo}/envoyerDocument', 'envoyerDocument')->name('documents');
        Route::post('/{CPFDocumentInfo}/envoyerFinFormation', 'envoyerFinFormation')->name('fin');
    });
});
