<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());
            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        app()->singleton(
            LoginResponse::class,
            function () {
                return new class implements LoginResponse {
                    public function toResponse($request)
                    {
                        $redirectTo = $request->input('redirect_to');
                        $user = Auth::user();

                        // Priority: Admin → Secretary → Monitor → Student
                        if ($redirectTo && $user?->hasRole('student')) {
                            return Inertia::location($redirectTo);
                        }

                        if ($user?->hasRole('admin')) {
                            return Inertia::location(route('admin.dashboard.index'));
                        }

                        if ($user?->hasRole('secretary')) {
                            return Inertia::location(route('secretary.dashboard.index'));
                        }

                        if ($user?->hasRole('monitor')) {
                            return Inertia::location(route('monitor.dashboard.index'));
                        }

                        if ($user?->hasRole('student')) {
                            return Inertia::location(route('student.dashboard.index'));
                        }

                        // Default fallback
                        return Inertia::location('/');
                    }
                };
            }
        );



    }
}
