<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';



    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        // Chat has a persistent socket, unread badge and catch-up sync. It needs
        // its own budget so normal dashboard use cannot block opening a thread.
        RateLimiter::for('chat', function (Request $request) {
            $identity = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(600)->by('chat:'.$identity);
        });
        RateLimiter::for('chat-write', function (Request $request) {
            $identity = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(120)->by('chat-write:'.$identity);
        });

        $this->routes(function () {


            /*
             *
             * APIS ROUTES
             *
             * admin
             * student
             * monitor
             *
             */
            Route::middleware('web')
                ->prefix('api')
                ->name('api.')
                ->group(base_path('routes/api.php'));


            Route::middleware('web')
                ->prefix('api/admin')
                ->name('api.admin.')
                ->group(base_path('routes/admin/api.php'));

            Route::middleware('web')
                ->prefix('api/student')
                ->name('api.student.')
                ->group(base_path('routes/student/api.php'));


            Route::middleware('web')
                ->prefix('api/monitor')
                ->name('api.monitor.')
                ->group(base_path('routes/monitor/api.php'));

            // blog routes
            // Route::middleware('web')
            //     ->prefix('api/blog')
            //     ->name('api.blog.')
            //     ->group(base_path('routes/blog/api.php'));


            /*
             *
             * WEB ROUTES
             *
             * admin
             * student
             * monitor
             * front
             * blog
             *
             */

            // Admin routes
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin/web.php'));

   Route::middleware('web')
                ->prefix('secretary')
                ->name('secretary.')
                ->group(base_path('routes/web.php'));

            // students routes
            Route::middleware('web')
                ->prefix('student')
                ->name('student.')
                ->group(base_path('routes/student/web.php'));

            // Monitor routes
            Route::middleware('web')
                ->prefix('monitor')
                ->name('monitor.')
                ->group(base_path('routes/monitor/web.php'));

            // Front routes
            Route::middleware('web')
                ->group(base_path('routes/client/web.php'));

            // // Blog routes
            // Route::middleware('web')
            //     ->prefix('blog')
            //     ->name('blog.')
            //     ->group(base_path('routes/blog/web.php'));


            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
