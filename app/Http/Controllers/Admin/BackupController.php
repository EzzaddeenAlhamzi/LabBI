<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\Ops\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class BackupController extends Controller
{
    public function index(): View
    {
        $backups = Backup::query()
            ->with(['creator', 'restorer'])
            ->latest('taken_at')
            ->paginate(25);

        return view('admin.backups.index', compact('backups'));
    }

    public function store(Request $request, BackupService $backups): RedirectResponse
    {
        try {
            $backup = $backups->create($request->user(), 'manual', 'Manual trigger from Admin UI');

            return redirect()
                ->route('admin.backups.index')
                ->with('success', 'تم إنشاء النسخة الاحتياطية بنجاح (#'.$backup->id.').');
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['backup' => 'فشل إنشاء النسخة الاحتياطية. راجع سجلات النظام.']);
        }
    }

    public function show(Backup $backup): View
    {
        $backup->load(['creator', 'restorer']);

        return view('admin.backups.show', compact('backup'));
    }

    public function restore(Request $request, Backup $backup, BackupService $backups): RedirectResponse
    {
        $data = $request->validate([
            'confirm_text' => ['required', 'in:RESTORE'],
            'acknowledge' => ['accepted'],
        ], [
            'confirm_text.in' => 'يجب كتابة RESTORE للتأكيد.',
            'acknowledge.accepted' => 'يجب الإقرار بأن الاستعادة عملية تدميرية.',
        ]);

        try {
            $backups->restore($backup, $request->user(), confirmed: true);

            return redirect()
                ->route('admin.backups.index')
                ->with('success', 'تمت استعادة النسخة #'.$backup->id.' — تحقق من سلامة البيانات فوراً.');
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['restore' => 'فشلت الاستعادة: '.$e->getMessage()]);
        }
    }
}
