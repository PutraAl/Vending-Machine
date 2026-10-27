<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\View\ViewServiceProvider;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->registered(function (Application $app): void {
        $viewProviders = array_values(array_filter(
            $app['config']->get('app.providers', []),
            fn($provider) => str_contains(
                $provider,
                'ViewServiceProvider'
            )
        ));

        error_log(
            '[laravel-diag] config_cached=' .
                ($app->configurationIsCached() ? 'yes' : 'no')
        );

        error_log(
            '[laravel-diag] view_provider_loaded=' .
                ($app->providerIsLoaded(
                    \Illuminate\View\ViewServiceProvider::class
                ) ? 'yes' : 'no')
        );

        error_log(
            '[laravel-diag] view_binding=' .
                ($app->bound('view') ? 'yes' : 'no')
        );

        error_log(
            '[laravel-diag] app.providers_view=' .
                json_encode($viewProviders)
        );

        error_log(
            '[laravel-diag] services_cache_path=' .
                $app->getCachedServicesPath()
        );
    })
    ->create();

// Pastikan layanan view tersedia sebelum request ditangani.
$app->register(ViewServiceProvider::class);

return $app;
