<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Isi TRUSTED_PROXIES bila aplikasi berada di balik reverse proxy (nginx/Cloudflare),
        // contoh: TRUSTED_PROXIES=* atau TRUSTED_PROXIES=172.16.0.0/12
        if ($trustedProxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $trustedProxies === '*' ? '*' : explode(',', $trustedProxies));
        }

        $middleware->web(append: [
            HandleInertiaRequests::class,
            SecurityHeadersMiddleware::class,
        ]);

        $middleware->api(append: [
            SecurityHeadersMiddleware::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'subscribed' => \App\Http\Middleware\EnsureActiveSubscription::class,
            'client.api' => \App\Http\Middleware\EnsureClientApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();
            if (in_array($status, [403, 404, 429, 500, 503])) {
                if (!$request->expectsJson() && !app()->runningUnitTests()) {
                    return Inertia::render('Error', ['status' => $status])
                        ->toResponse($request)
                        ->setStatusCode($status);
                }
            }
            return $response;
        });
    })->create();
