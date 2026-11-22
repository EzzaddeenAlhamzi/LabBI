@extends('layouts.admin')
@section('heading', 'التقارير PDF')
@section('subheading', 'التقارير المعتمدة لمرضاك')
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>المريض</th><th>الفحص</th><th>الطلب</th><th>تاريخ الإنشاء</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($reports as $report)
    <tr>
        <td>{{ $report->patient?->full_name }}</td>
        <td>{{ $report->result?->test?->name_ar }}</td>
        <td dir="ltr">{{ $report->order?->order_number }}</td>
        <td class="muted-cell" dir="ltr">{{ $report->generated_at }}</td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('reports.download', $report) }}"><x-icon name="download" /> تنزيل</a></div></td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><div class="empty-icon"><x-icon name="file-text" /></div><h3>لا توجد تقارير</h3><p>لا توجد تقارير PDF معتمدة بعد.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($reports->hasPages())<div class="pager">{{ $reports->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
