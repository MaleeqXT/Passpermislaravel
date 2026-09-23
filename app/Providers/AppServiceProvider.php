<?php

namespace App\Providers;

use App\Services\Cpf\CpfInterface;
use App\Services\Cpf\CpfService;
use App\Services\Payment\Paypal\Payment\PaymentInterface;
use App\Services\Payment\Paypal\Payment\PaymentService;
use App\Services\Student\Training\Cancellation\CancellationInterface;
use App\Services\Student\Training\Cancellation\CancellationService;
use App\Services\Student\Training\info\TrainingInterface;
use App\Services\Student\Training\info\TrainingService;
use App\Services\Student\Training\Proposal\ProposalInterface;
use App\Services\Student\Training\Proposal\ProposalService;
use App\Services\Student\Training\Reservation\info\ReservationInterface;
use App\Services\Student\Training\Reservation\info\ReservationService;
use App\Services\Student\Training\Reservation\info\UnrestrictedReservationService;
use App\Services\Student\Training\Reservation\params\ReservationPramsInterface;
use App\Services\Student\Training\Reservation\params\ReservationPramsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // payment service stripe, paypal, etc.
        $this->app->bind(PaymentInterface::class, PaymentService::class);

        // reservation service (create reservation, delete reservation, get reservation, etc.)
        $this->app->bind(ReservationInterface::class, ReservationService::class);
        
        // unrestricted reservation service (allows multiple students per monitor)
        $this->app->bind(UnrestrictedReservationService::class, UnrestrictedReservationService::class);

        // session service (create session, delete session, get session, etc.)
        $this->app->bind(TrainingInterface::class, TrainingService::class);

        $this->app->bind(ProposalInterface::class, ProposalService::class);
        // Annulation tardive  service (check annulation, create annulation, etc.)
        $this->app->bind(CancellationInterface::class, CancellationService::class);

        // setting reservation service (create setting reservation, delete setting reservation, get setting reservation, etc.)
        $this->app->bind(ReservationPramsInterface::class, ReservationPramsService::class);

        // CpfEleve service (create cpf student, delete cpf student, get cpf student, etc.)
        $this->app->bind(CpfInterface::class, CpfService::class);
    }
}
