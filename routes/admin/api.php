<?php

// use App\Http\Controllers\Backend\Front\Offers\OffersController;
use App\Http\Controllers\V1\EndPoint\Monitor\Invoice\InvoiceController;
use App\Http\Controllers\V1\EndPoint\Monitor\Reservation\ReservationAdminController;
// use App\Http\Controllers\V1\EndPoint\Monitor\User\UserMonitorController;
use App\Http\Controllers\V1\Inertia\Monitor\User\Info\UserMonitorController;
use App\Http\Controllers\V1\Inertia\Student\Sale\AdminSaleController;

use App\Http\Controllers\V1\EndPoint\Student\Info\AvailableStudentController;
use App\Http\Controllers\V1\EndPoint\Student\Info\CommentStudentController;
use App\Http\Controllers\V1\EndPoint\Student\Info\InfoHourStudentController;
use App\Http\Controllers\V1\EndPoint\Student\Info\CompetencyStudentInfoController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\CartController;
use App\Http\Controllers\V1\EndPoint\Student\Sale\Type\StripController;
use App\Http\Controllers\V1\EndPoint\Student\User\UserStudentController;
use App\Http\Controllers\V1\EndPoint\Student\Document\AdminStudentDocumentController;
use  App\Http\Controllers\V1\EndPoint\Monitor\Reservation\UnrestrictiveReservationAdminController;
use App\Http\Controllers\V1\EndPoint\Student\User\Wallet\WalletController;
use App\Http\Controllers\V1\EndPoint\System\Offre\OffreController;
use App\Http\Controllers\V1\EndPoint\System\User\UserController;
use App\Http\Controllers\V1\EndPoint\System\Zone\ZoneController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\AdminProposalTrainingController;
//school handling
use App\Http\Controllers\V1\EndPoint\Schools\SchoolController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SatisfactionController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\V1\Inertia\CPF\CPFPagesController;

use App\Http\Controllers\V1\Inertia\System\User\AdminUserController;
use App\Http\Controllers\V1\Inertia\Student\User\StudentUserController;
use App\Http\Controllers\V1\EndPoint\RdvPermis\StudentMandateController;
// use App\Http\Controllers\V1\Inertia\Monitor\User\Info\UserMonitorController;
use App\Http\Controllers\V1\Inertia\Monitor\User\Info\InformationMonitorController;

use App\Http\Controllers\V1\Inertia\System\Zone\LieuxController;

use App\Http\Controllers\V1\Inertia\Admin\HomePageController;

use App\Http\Controllers\SecretaryController;

use App\Http\Controllers\V1\Inertia\Student\Competence\CompetenceAdminController;
use App\Http\Controllers\V1\Inertia\Student\Competence\MainCompetenceAdminController;

use App\Http\Controllers\V1\Inertia\System\Promo\OffresController;

use App\Http\Controllers\V1\Inertia\Student\Sale\AdminCartController;
use App\Http\Controllers\V1\Inertia\Student\Infrastructure\Training\AdminCancellationController;
use App\Http\Controllers\V1\EndPoint\Admin\Exam\AdminExamController;


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
    //new Route.


// http://localhost:8000/api/admin/locations/9ef09b69-fb67-42b4-9c2f-1c102ba5f4fb/places
    Route::prefix('/{zone}')->group(function () {
        Route::get('/zips', 'getAllZips')->name('zips.index');
        Route::get('/places', 'getAllLieux')->name('places.index');
    });
});

Route::middleware(['auth:sanctum', 'role:admin|secretary', config('jetstream.auth_session'), 'verified', 'slow'])->group(function () {
      Route::prefix('student-documents')->controller(AdminStudentDocumentController::class)->group(function () {
          Route::get('/', 'index');
          Route::get('/{student}', 'show');
          Route::patch('/{student}/status', 'updateStudentStatus');
          Route::patch('/{student}/documents/{documentType}', 'update');
          Route::get('/{student}/documents/{documentType}/download', 'download');
      });

      Route::prefix('exams')->controller(AdminExamController::class)->group(function () {
          Route::get('/', 'index');
          Route::put('/{student}', 'update');
      });


      Route::prefix('forms-cpf')->controller(CPFPagesController::class)->group(function () {
          Route::get('/', 'adminRecords');
          Route::put('/{CPFDocumentInfo}', 'updateAdminRecord');
          Route::post('/{CPFDocumentInfo}/send-documents', 'sendDocumentsAdmin');
          Route::post('/{CPFDocumentInfo}/send-completion-certificate', 'sendCompletionCertificateAdmin');
      });

      Route::get('/vehicles', [VehicleController::class, 'index']);
      Route::get('/vehicles/monitors', [VehicleController::class, 'monitors']);
      Route::post('/vehicles', [VehicleController::class, 'store']);
      Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update']);
      Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy']);
      Route::prefix('satisfaction')->controller(SatisfactionController::class)->group(function () {
        Route::get('/surveys', 'adminSurveys');
        Route::get('/responses', 'adminList');
        Route::get('/statistics', 'statistics');
        Route::get('/responses/{response}', 'adminShow');
      });

      Route::prefix('dashboard')->name('dashboard.')
        ->controller(HomePageController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });

      Route::post('students/{student}/rdvpermis/mandate', [StudentMandateController::class, 'store'])
          ->name('students.rdvpermis.mandate');



     Route::post('/schools/select/{school}', [SchoolController::class, 'selectSchool'])->name('schools.select');

    Route::prefix('schools')->name('schools.')
        ->controller(SchoolController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{school}', 'show')->name('show');
        });


      // ========================== Users Management ==========================
    Route::prefix('users')->name('users.')->group(function () {

        //   Route::get('/getAdmins', [AdminUserController::class,'index'])->name('index');
        // ----- Admin Users -----
        Route::prefix('admins')->name('admins.')
            ->controller(AdminUserController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/store', 'store')->name('store');
                Route::prefix('{user}')->group(function () {
                    Route::get('/', 'edit')->name('edit');
                    Route::put('/', 'update')->name('update');
                    Route::delete('/', 'destroy')->name('destroy');
                });

                Route::get('/status/{user}',[AdminUserController::class,'changeStatus']);
            });


                    Route::prefix('secretaries')->name('secretaries.')->group(function () {
                Route::get('/index', [SecretaryController::class, 'index'])->name('index');
            Route::get('/create', [SecretaryController::class, 'create'])->name('create'); // form page

            Route::post('/', [SecretaryController::class, 'store'])->name('store');  // save new secretary
            Route::get('/{secretary}', [SecretaryController::class, 'show'])->name('show'); //geting user by id
            Route::post('/update/{secretary}', [SecretaryController::class, 'update'])->name('update');

        });

        // ----- Monitors -----
        Route::prefix('monitors')->name('monitors.')
            ->controller(UserMonitorController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                // Route::get('/create', 'create')->name('create');
                Route::post('/store', 'store')->name('store');
                Route::prefix('{monitor}')->group(function () {
                    Route::get('/factures', 'invoices')->name('invoices');
                    Route::get('/edit', 'edit')->name('edit');
                    Route::put('/status', 'updateStatus')->name('update.status');
                    Route::put('/', 'update')->name('update');
                    Route::delete('/', 'destroy')->name('destroy');
                });
                Route::prefix('{monitor}')->controller(InformationMonitorController::class)->group(function () {
                    Route::get('/documents', 'documents')->name('documents');
                    Route::put('/documents', 'documentsUpdate')->name('documents.update');
                });
            });

        // ----- Students -----
        Route::get('users/students/{student}/contract', [StudentUserController::class, 'contract'])
            ->name('users.students.contract');

        Route::prefix('students')->name('students.')
            ->controller(StudentUserController::class)->group(function () {
                Route::get('/cpf-form', 'cpfFormStudents')->name('cpf.form');
                Route::get('/create', 'create')->name('create');
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::prefix('{student}')->group(function () {
                    Route::get('/contract', 'contract')->name('contract');
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


        Route::prefix('{user}')->controller(UserController::class)->group(function () {
            Route::put('', 'archiveUser')->name('archive');
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

                // ========================== cancellations ==========================


    //places

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


           Route::name('cancellations')->prefix('cancellations')
        ->controller(AdminCancellationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::prefix('{cancellation}')->group(function () {
                Route::put('/', 'update')->name('update');
            });
        });


            Route::prefix('commandes')->name('commandes.')
        ->controller(AdminSaleController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::prefix('{sale}')->group(function () {
                Route::get('/', 'show')->name('show');
            });
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


 Route::prefix('proposals')->name('proposals.')
        ->controller(AdminProposalTrainingController::class)->group(function () {
            Route::get('/', 'proposals')->name('index');
        });
    Route::prefix('reservations')->name('reservations.')
        ->controller(ReservationAdminController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/events', 'events')->name('events');
            Route::post('/store-unrestricted', 'storeUnrestricted')->name('store_unrestricted');
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

    // Unrestrictive reservations separate endpoints
    Route::prefix('reservations-unrestricted')->name('reservations.unrestricted.')
        ->controller(UnrestrictiveReservationAdminController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{id}', 'show')->name('show');
            Route::post('/store', 'store')->name('store');
            Route::put('/update/{id}', 'update')->name('update');
            Route::delete('/destroy/{id}', 'destroy')->name('destroy');

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


    Route::prefix('user')->controller(UserController::class)
        ->name('user.')->group(function () {
            Route::prefix('/{user}')->group(function () {
                Route::put('', 'archiveUser')->name('archiveUser');
            });
        });


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

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
});
