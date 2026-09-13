<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'active' => \App\Http\Middleware\EnsureActiveUser::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Trust reverse-proxy headers when deployed behind TLS terminator.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
                if (! config('app.debug') && $status >= 500) {
                    return response()->json(['message' => 'حدث خطأ في الخادم.'], $status);
                }

                return null;
            }

            if (config('app.debug')) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            if (view()->exists('errors.'.$status)) {
                return response()->view('errors.'.$status, ['exception' => $e], $status);
            }

            if ($status >= 500) {
                return response()->view('errors.500', ['exception' => $e], 500);
            }

            return null;
        });
    })->create();
