<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SetAppLocale::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                // Handle ValidationException specifically
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'code' => 422,
                        'message' => 'validation.failed',
                        'message_text' => trans('validation.failed'),
                        'data' => null,
                        'errors' => $e->errors(),
                    ], 422);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'code' => 401,
                        'message' => 'auth.unauthorized',
                        'message_text' => trans('auth.unauthorized'),
                        'data' => null,
                        'errors' => null,
                    ], 401);
                }

                // Handle AuthorizationException (Spatie Permission)
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException || $e instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
                    return response()->json([
                        'success' => false,
                        'code' => 403,
                        'message' => 'permission.denied',
                        'message_text' => trans('permission.denied'),
                        'data' => null,
                        'errors' => null,
                    ], 403);
                }

                // Generic Server Error
                return response()->json([
                    'success' => false,
                    'code' => $statusCode,
                    'message' => 'server.error',
                    'message_text' => trans('server.error'), // Always localized
                    'data' => null,
                    'errors' => config('app.debug') ? [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTrace(),
                    ] : null,
                ], $statusCode >= 100 && $statusCode < 600 ? $statusCode : 500);
            }
        });
    })->create();
