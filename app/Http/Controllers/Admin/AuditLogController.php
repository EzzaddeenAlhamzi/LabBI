<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('user')->latest('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $action = trim((string) $request->input('action'));
            $query->where('action', 'like', '%'.$action.'%');
        }

        if ($request->filled('entity')) {
            $entity = trim((string) $request->input('entity'));
            $query->where(function ($q) use ($entity) {
                $q->where('auditable_type', 'like', '%'.$entity.'%')
                    ->orWhere('auditable_id', $entity);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate(30)->withQueryString();

        $users = User::query()->orderBy('name')->limit(200)->get(['id', 'name', 'email']);

        return view('admin.audit.index', [
            'logs' => $logs,
            'users' => $users,
            'filters' => $request->only(['user_id', 'action', 'entity', 'date_from', 'date_to']),
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('admin.audit.show', [
            'log' => $auditLog,
            'metadata' => $auditLog->safeMetadata(),
        ]);
    }
}
