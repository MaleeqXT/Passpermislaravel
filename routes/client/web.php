<?php

use App\Http\Controllers\Backend\Front\AgenciesController;
use App\Http\Controllers\Backend\Front\HomeController;
use App\Http\Controllers\Backend\Front\CodeController;
use App\Http\Controllers\Backend\Front\ContactController;
use App\Http\Controllers\Backend\Front\CPFController;
use App\Http\Controllers\Backend\Front\DevenirMoniteurController;
use App\Http\Controllers\Backend\Front\TermsAndConditionController;
use App\Http\Controllers\Backend\Front\Offers\OffersController;
use App\Http\Controllers\Backend\Front\CartController;
use App\Http\Controllers\Backend\Front\StudentsController;
use App\Http\Controllers\Backend\Front\VideosController;
use App\Http\Controllers\V1\Inertia\CPF\CPFPagesController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

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

Route::middleware(['slow'])->group(function () {


    // CPFController
    // Route::name('auth.')->prefix('auth')->controller(UsersEleveController::class)->group(function () {
    //     Route::get('/google/callback', 'google')->name('google.callback');
    // });

    Route::get('/auth/google/redirect', function () {
        return Socialite::driver('google')->redirect();
    });


    // home
    Route::name('home.')->controller(HomeController::class)->group(function () {

        Route::get('/', 'index')->name('index');
        Route::get('/home', 'index')->name('indexz');

    });

    Route::name('videos.')->prefix('videos')->controller(VideosController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });

    Route::name('agencies.')->prefix('agencies')->controller(AgenciesController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });


    Route::prefix('offers')->name('offers.')
        ->controller(OffersController::class)->group(function () {
            Route::get('/', 'index')->name('index');
        });

    // CPFController
    Route::name('cpf.')->prefix('cpf')->controller(CPFController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
    });

    Route::name('code.')->prefix('code')->controller(CodeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });


    Route::name('checkout')->prefix('checkout')->controller(CartController::class)
        ->group(function () {
            Route::get('/', 'index');
        });

    Route::prefix('register')->name('register.')->group(function () {
        Route::prefix('student')->name('student.')->controller(StudentsController::class)->group(function () {
            Route::get('/', 'create')->name('create');
            Route::post('/', 'store')->name('store');
        });
        Route::prefix('monitor')->name('monitor.')->controller(DevenirMoniteurController::class)->group(function () {
            Route::get('/', 'create')->name('create');
            Route::get('/setup-password/{user}', 'setupPassword')->name('setup-password');
            Route::post('/', 'store')->name('store');
        });
    });

    Route::name('contact.')->prefix('contact')->controller(ContactController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/send', 'send')->name('send');
    });




    Route::name('legal.')->controller(TermsAndConditionController::class)->group(function () {
        Route::get('/mention-legales', 'legalMentions')->name('mention-legales');
        Route::get('/politique-confidentialite', 'privacyPolicy')->name('politique-confidentialite');
        Route::get('/conditions-utilisation', 'termsOfUse')->name('conditions-utilisation');
        Route::get('/conditions-vente', 'termsOfSale')->name('conditions-vente');
    });
});

Route::name('formation-permis-b.')->controller(CPFPagesController::class)->group(function () {
    Route::get('/formation-permis-b', 'formationPermisBInfo')->name('index');
    Route::get('/formation-permis-b/pdf', 'formationPermisBPdf')->name('pdf');
});


Route::name('forms-cpf.')->prefix('forms-cpf')->controller(CPFPagesController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/finish', 'finish')->name('finish');
    Route::name('test.positionnement.')->prefix('test-positionnement')->group(function () {
        Route::post('/', 'storeTestPos')->name('index');
        Route::get('/{CPFDocumentInfo}', 'storeTestPosPdf')->name('pdf');
    });
    Route::name('contact.formation.')->prefix('contact-formation/{CPFDocumentInfo}')->group(function () {
        Route::get('/', 'contactFormation')->name('index');
        Route::get('/pdf', 'contactFormationPdf')->name('pdf');
    });

    Route::name('reservations.')->prefix('reservations/{CPFDocumentInfo}')->group(function () {
        Route::get('/pdf', 'reservationsPdf')->name('pdf');
    });

    Route::name('attestation.honneur.')->prefix('attestation-honneur/{CPFDocumentInfo}')->group(function () {
        Route::get('/', 'attestationHonneur')->name('index');
        Route::get('/pdf', 'attestationHonneurPdf')->name('pdf');
        Route::post('/', 'storeAttestaion')->name('store');
    });
    Route::name('attestation-fin-formation.')->prefix('attestation-fin-formation/{CPFDocumentInfo}')->group(function () {
        Route::get('/pdf', 'affPdf')->name('pdf');
    });
});


// Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',])->group(function () {});
