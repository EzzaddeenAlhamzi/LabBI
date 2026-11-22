@extends('layouts.admin')
@section('title', 'سجل العمليات')
@section('heading', 'سجل العمليات')
@section('subheading', 'سجل تدقيق للإدارة — بيانات وصفية آمنة بدون أسرار')
@section('content')
<div class="card">
    <form method="get" class="form-grid" style="grid-template-columns: repeat(4, 1fr); align-items:end">
        <div class="field" style="margin-bottom:0">
            <label for="user_id">المستخدم</label>
            <select name="user_id" id="user_id">
                <option value="">الكل</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected(($filters['user_id'] ?? '') == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin-bottom:0">
            <label for="action">الإجراء</label>
            <input type="text" name="action" id="action" value="{{ $filters['action'] ?? '' }}" placeholder="مثل auth.login">
        </div>
        <div class="field" style="margin-bottom:0">
            <label for="entity">الكيان / المعرف</label>
            <input type="text" name="entity" id="entity" value="{{ $filters['entity'] ?? '' }}" placeholder="Patient أو رقم">
        </div>
        <div class="field" style="margin-bottom:0">
            <label for="date_from">من تاريخ</label>
            <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="field" style="margin-bottom:0">
            <label for="date_to">إلى تاريخ</label>
            <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="field" style="margin-bottom:0;grid-column:1/-1">
            <div class="flex" style="gap:.5rem">
                <button class="btn" type="submit"><x-icon name="search" /> تصفية</button>
                <a class="btn btn-ghost" href="{{ route('admin.audit.index') }}">إعادة تعيين</a>
            </div>
        </div>
    </form>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>الوقت</th>
                <th>المستخدم</th>
                <th>الإجراء</th>
                <th>الكيان</th>
                <th>IP</th>
                <th class="text-end">إجراءات</th>
            </tr>
        </thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td class="muted-cell">{{ $log->id }}</td>
                <td class="muted-cell" dir="ltr" style="text-align:start">{{ $log->created_at }}</td>
                <td>{{ $log->user?->name ?? '—' }}</td>
                <td><code>{{ $log->action }}</code></td>
                <td class="muted-cell">
                    @if($log->auditable_type)
                        {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                    @else
                        —
                    @endif
                </td>
                <td class="muted-cell" dir="ltr" style="text-align:start">{{ $log->ip_address }}</td>
                <td><div class="row-actions"><a class="btn btn-ghost btn-sm" href="{{ route('admin.audit.show', $log) }}"><x-icon name="eye" /> تفاصيل</a></div></td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="empty"><div class="empty-icon"><x-icon name="scroll" /></div><h3>لا توجد سجلات</h3><p>لا توجد عمليات مطابقة لمعايير البحث.</p></div></td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    @if($logs->hasPages())<div class="pager">{{ $logs->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
