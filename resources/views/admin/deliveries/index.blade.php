@extends('layouts.admin')
@section('title', 'التوصيل والتقارير')
@section('heading', 'التوصيل والتقارير')
@section('subheading', 'رؤية الإدارة لحالات الإرسال وأرشيف PDF')
@section('content')
<div class="card flush">
    <div class="card-title" style="padding:1rem 1rem 0"><x-icon name="truck" /> تتبع التوصيل ({{ $deliveries->total() }})</div>
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>القناة</th><th>المستخدم</th><th>المستلم</th><th>الحالة</th><th class="num">محاولات</th><th>مرجع المزود</th><th>الخطأ</th><th>الوقت</th></tr></thead>
    <tbody>
    @forelse($deliveries as $d)
    <tr>
        <td><span class="badge badge-neutral">{{ $d->channel }}</span></td>
        <td>{{ $d->notification?->user?->name ?? '—' }}</td>
        <td dir="ltr" style="text-align:start">{{ $d->recipient }}</td>
        <td><x-status-badge :status="$d->status" :label="$d->status" /></td>
        <td class="num">{{ $d->attempts }}</td>
        <td class="muted-cell" dir="ltr" style="text-align:start">{{ $d->provider_ref ?: '—' }}</td>
        <td class="muted-cell" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $d->error_summary }}">{{ $d->error_summary ?: '—' }}</td>
        <td class="muted-cell">{{ $d->sent_at ?? $d->failed_at ?? $d->created_at }}</td>
    </tr>
    @empty
    <tr><td colspan="8"><div class="empty"><div class="empty-icon"><x-icon name="truck" /></div><h3>لا سجلات توصيل بعد</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($deliveries->hasPages())<div class="pager">{{ $deliveries->links('admin.partials.pagination') }}</div>@endif
</div>

<div class="card flush">
    <div class="card-title" style="padding:1rem 1rem 0"><x-icon name="file-text" /> أرشيف التقارير المُنشأة ({{ $reports->total() }})</div>
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>المريض</th><th>الفحص</th><th>الطلب</th><th>مفتاح التخزين</th><th>تاريخ الإنشاء</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($reports as $report)
    <tr>
        <td><b>{{ $report->patient?->full_name ?? '—' }}</b></td>
        <td>{{ $report->result?->test?->name_ar ?? '—' }}</td>
        <td><code>{{ $report->order?->order_number ?? '—' }}</code></td>
        <td class="muted-cell" dir="ltr" style="text-align:start;font-size:.78rem">{{ $report->storage_key }}</td>
        <td class="muted-cell">{{ $report->generated_at }}</td>
        <td><div class="row-actions"><a class="btn btn-ghost btn-sm" href="{{ route('reports.download', $report) }}"><x-icon name="download" /> تنزيل</a></div></td>
    </tr>
    @empty
    <tr><td colspan="6"><div class="empty"><div class="empty-icon"><x-icon name="file-text" /></div><h3>لا تقارير</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($reports->hasPages())<div class="pager">{{ $reports->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
