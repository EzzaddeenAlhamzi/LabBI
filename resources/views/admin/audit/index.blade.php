@extends('layouts.app')
@section('heading', 'سجل العمليات')
@section('subheading', 'سجل تدقيق للإدارة — بيانات وصفية آمنة بدون أسرار')
@section('content')
<div class="card">
    <form method="get" class="grid-2" style="align-items:end">
        <div>
            <label for="user_id">المستخدم</label>
            <select name="user_id" id="user_id">
                <option value="">الكل</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected(($filters['user_id'] ?? '') == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="action">الإجراء</label>
            <input type="text" name="action" id="action" value="{{ $filters['action'] ?? '' }}" placeholder="مثل auth.login أو results.approved">
        </div>
        <div>
            <label for="entity">الكيان / المعرف</label>
            <input type="text" name="entity" id="entity" value="{{ $filters['entity'] ?? '' }}" placeholder="Patient أو رقم">
        </div>
        <div class="grid-2">
            <div>
                <label for="date_from">من تاريخ</label>
                <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div>
                <label for="date_to">إلى تاريخ</label>
                <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
        </div>
        <div class="actions">
            <button class="btn" type="submit">تصفية</button>
            <a class="btn btn-ghost" href="{{ route('admin.audit.index') }}">إعادة تعيين</a>
        </div>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>الوقت</th>
                <th>المستخدم</th>
                <th>الإجراء</th>
                <th>الكيان</th>
                <th>IP</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td>{{ $log->id }}</td>
                <td>{{ $log->created_at }}</td>
                <td>{{ $log->user?->name ?? '—' }}</td>
                <td><span class="badge">{{ $log->action }}</span></td>
                <td class="muted" style="font-size:0.85rem">
                    @if($log->auditable_type)
                        {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                    @else
                        —
                    @endif
                </td>
                <td>{{ $log->ip_address }}</td>
                <td><a href="{{ route('admin.audit.show', $log) }}">تفاصيل</a></td>
            </tr>
        @empty
            <tr><td colspan="7">لا توجد سجلات مطابقة.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection
