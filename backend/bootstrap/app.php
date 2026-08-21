<?php

use App\Http\Middleware\Admin;
use App\Http\Middleware\Feature;
use App\Http\Middleware\Permission;
use App\Http\Middleware\PlatformAdminAuthenticated;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\ResolveTenantForSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // TenantContext must exist before StartSession/Auth attempt to reload
        // a tenant-scoped User from a database-backed session. Register the
        // lightweight host resolver globally so it wraps the complete web
        // middleware lifecycle, including session persistence.
        $middleware->prepend(
            ResolveTenantForSession::class
        );

        $middleware->web(prepend: [
            ResolveTenant::class,
        ]);

        $middleware->preventRequestForgery(except: [
            'webhooks/whatsapp/*',
            'webhooks/payment/*',
        ]);

        $middleware->alias([
    'admin' => Admin::class,
    'permission' => Permission::class,
        'feature' => Feature::class,
        'platform.admin' => PlatformAdminAuthenticated::class,
]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(
            function (
                \App\Exceptions\UsageBlockedException $exception,
                \Illuminate\Http\Request $request
            ) {
                if (
                    $exception->reason !==
                    'limit_exceeded'
                ) {
                    return null;
                }

                if ($request->expectsJson()) {
                    return response()->json(
                        [
                            'message' =>
                                __('errors.usage_limit.message'),

                            'upgrade_suggested' =>
                                $exception->upgradeSuggested,

                            'usage' => [
                                'metric' =>
                                    $exception->metric->value,

                                'used' =>
                                    $exception->used,

                                'requested' =>
                                    $exception->requested,

                                'limit' =>
                                    $exception->limit,

                                'remaining' =>
                                    $exception->remaining,
                            ],
                        ],
                        429
                    );
                }

                return response()->view(
                    'errors.429',
                    [
                        'exception' =>
                            $exception,
                    ],
                    429
                );
            }
        );

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*')
                || $request->expectsJson(),
        );
    })->create();