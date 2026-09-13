<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Non-secret system settings surface only. Secrets remain env-only.
 */
class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => [
                'app_name' => config('app.name'),
                'app_env' => config('app.env'),
                'app_debug' => (bool) config('app.debug'),
                'app_url' => config('app.url'),
                'locale' => config('app.locale'),
                'queue_connection' => config('queue.default'),
                'mail_mailer' => config('mail.default'),
                'whatsapp_driver' => config('labbi.whatsapp.driver', env('WHATSAPP_DRIVER', 'mock')),
                'backup_disk' => config('labbi.backup.disk'),
                'backup_schedule' => config('labbi.backup.schedule'),
                'session_lifetime' => config('session.lifetime'),
                'force_https' => (bool) config('labbi.force_https'),
            ],
        ]);
    }

    public function touch(Request $request, AuditLogger $audit): RedirectResponse
    {
        // No secret mutation — records that an admin viewed/acked settings.
        $audit->log('settings.viewed', null, null, [
            'ack' => true,
        ], $request->user()?->id);

        return back()->with('success', 'تم تسجيل مراجعة الإعدادات. الأسرار تُدار عبر متغيرات البيئة فقط.');
    }
}
