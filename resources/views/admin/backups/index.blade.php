@extends('layouts.admin')
@section('title', 'النسخ الاحتياطي')
@section('heading', 'إدارة النسخ الاحتياطي')
@section('subheading', 'نسخ قاعدة البيانات والملفات الخاصة — الاستعادة عملية تدميرية')
@section('content')
<div class="card">
    <div class="flex-between">
        <div class="flex" style="gap:.6rem;align-items:center">
            <span class="kpi-icon tone-blue" style="width:40px;height:40px"><x-icon name="database" /></span>
            <div>
                <b>القرص: {{ config('labbi.backup.disk') }}</b>
                <div class="muted small">الجدولة: يومياً {{ config('labbi.backup.schedule_time') }}</div>
            </div>
        </div>
        <form method="post" action="{{ route('admin.backups.store') }}">@csrf
            <button class="btn" type="submit"><x-icon name="plus" /> تشغيل نسخة يدوية</button>
        </form>
    </div>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>الوقت</th>
                <th>الحالة</th>
                <th>الحجم</th>
                <th>التخزين</th>
                <th>المشغّل</th>
                <th>المسار</th>
                <th class="text-end">إجراءات</th>
            </tr>
        </thead>
        <tbody>
        @forelse($backups as $backup)
            <tr>
                <td class="muted-cell">{{ $backup->id }}</td>
                <td class="muted-cell" dir="ltr" style="text-align:start">{{ $backup->taken_at }}</td>
                <td><x-status-badge :status="$backup->status" :label="$backup->status" /></td>
                <td>{{ $backup->humanSize() }}</td>
                <td><span class="badge badge-neutral">{{ $backup->disk }}</span></td>
                <td>{{ $backup->trigger }} / {{ $backup->creator?->name ?? 'نظام' }}</td>
                <td class="muted-cell" dir="ltr" style="text-align:start;font-size:.78rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $backup->storage_path }}">{{ $backup->storage_path }}</td>
                <td><div class="row-actions"><a class="btn btn-ghost btn-sm" href="{{ route('admin.backups.show', $backup) }}"><x-icon name="settings" /> إدارة</a></div></td>
            </tr>
        @empty
            <tr><td colspan="8"><div class="empty"><div class="empty-icon"><x-icon name="database" /></div><h3>لا توجد نسخ بعد</h3><p>شغّل نسخة يدوية أو انتظر الجدولة اليومية.</p></div></td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    @if($backups->hasPages())<div class="pager">{{ $backups->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
