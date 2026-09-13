<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request, AuditLogger $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request, $audit);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request), (int) config('labbi.security.login_decay_seconds', 60));

            $audit->security('auth.login_failed', [
                'email' => $credentials['email'],
            ]);

            return back()->withErrors(['email' => 'بيانات الدخول غير صحيحة.'])->onlyInput('email');
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $audit->security('auth.login_inactive', [
                'email' => $credentials['email'],
            ], $user->id);

            return back()->withErrors(['email' => 'تم تعطيل هذا الحساب.'])->onlyInput('email');
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->log('auth.login', $user);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request, AuditLogger $audit): RedirectResponse
    {
        if ($user = $request->user()) {
            $audit->log('auth.logout', $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function ensureIsNotRateLimited(Request $request, AuditLogger $audit): void
    {
        $max = (int) config('labbi.security.login_max_attempts', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), $max)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));
        $audit->security('auth.login_throttled', [
            'email' => (string) $request->input('email'),
            'retry_after' => $seconds,
        ]);

        throw ValidationException::withMessages([
            'email' => 'محاولات كثيرة. حاول بعد '.$seconds.' ثانية.',
        ])->status(429);
    }

    protected function throttleKey(Request $request): string
    {
        return strtolower((string) $request->input('email')).'|'.$request->ip();
    }
}
