<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationOrchestrator;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationOrchestrator $orchestrator): View
    {
        abort_unless($request->user()->hasPermission('notifications.view_self'), 403);

        $notifications = Notification::query()
            ->with(['patient', 'test', 'order', 'result', 'deliveries'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        $unread = $orchestrator->unreadCount($request->user());

        return view('notifications.index', compact('notifications', 'unread'));
    }

    public function show(Request $request, Notification $notification, NotificationOrchestrator $orchestrator): View
    {
        abort_unless($request->user()->hasPermission('notifications.view_self'), 403);
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->load(['patient', 'test', 'order', 'result', 'deliveries']);
        try {
            $orchestrator->markRead($notification, $request->user());
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        return view('notifications.show', compact('notification'));
    }

    public function markRead(Request $request, Notification $notification, NotificationOrchestrator $orchestrator): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $orchestrator->markRead($notification, $request->user());

        return back()->with('success', 'تم تعليم الإشعار كمقروء.');
    }

    public function markAllRead(Request $request, NotificationOrchestrator $orchestrator): RedirectResponse
    {
        $orchestrator->markAllRead($request->user());

        return back()->with('success', 'تم تعليم جميع الإشعارات كمقروءة.');
    }
}
