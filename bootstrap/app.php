<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
// use Spatie\Permission\Middlewares\RoleMiddleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php', [
        'prefix' => 'api',
        'middleware' => ['web', 'auth:sanctum', \App\Http\Middleware\ResolveMonitorChat::class, 'throttle:120,1'],
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'slow' => \App\Http\Middleware\SimulateSlowResponse::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
         $middleware->validateCsrfTokens(except: [
            'api/*',  // ✅ yeh add karo
        ]);
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
        // Identifiers are opaque strings, including provider-accepted whitespace.
        $middleware->trimStrings(except: ['neph', 'filtre.numeroDossier.query']);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
        //
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // Model binding/session failures can happen before RdvPermis controllers.
        $exceptions->render(function (\Illuminate\Database\QueryException $exception, \Illuminate\Http\Request $request) {
            if ($request->is('api/rdvpermis', 'api/rdvpermis/*', 'api/admin/students/*/rdvpermis/*')) {
                return response()->json(['message' => 'La demande RdvPermis n’a pas pu être traitée. Réessayez plus tard.'], 500);
            }
        });
    })
    ->create();

$app->singleton(ConsoleKernelContract::class, \App\Console\Kernel::class);

return $app;
