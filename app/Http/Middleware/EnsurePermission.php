<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * @param  string  ...$permissions  Comma-separated groups OK; any permission grants access.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'غير مصرح');
        }

        $codes = [];
        foreach ($permissions as $permission) {
            foreach (explode(',', $permission) as $code) {
                $code = trim($code);
                if ($code !== '') {
                    $codes[] = $code;
                }
            }
        }

        foreach ($codes as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
    }
}
