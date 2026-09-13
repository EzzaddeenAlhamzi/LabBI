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

        // Production-aware trusted proxies (TRUSTED_PROXIES env).
        // Empty/unset → do not trust X-Forwarded-* (prevents client spoofing).
        // Comma-separated IPs/CIDRs → trust only those edges.
        // Explicit "*" → trust all (only when the app is unreachable except via a locked reverse proxy).
        $trustedProxies = env('TRUSTED_PROXIES');
        if (is_string($trustedProxies) && trim($trustedProxies) !== '') {
            $at = trim($trustedProxies) === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', $trustedProxies))));

            if ($at === '*' || $at !== []) {
                $middleware->trustProxies(
                    at: $at,
                    headers: Request::HEADER_X_FORWARDED_FOR
                        | Request::HEADER_X_FORWARDED_HOST
                        | Request::HEADER_X_FORWARDED_PORT
                        | Request::HEADER_X_FORWARDED_PROTO
                        | Request::HEADER_X_FORWARDED_AWS_ELB
                );
            }
        }
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
