@extends('layouts.app')
@section('heading', 'التقارير PDF')
@section('subheading', 'التقارير المعتمدة لمرضاك')
@section('content')
<div class="card">
<table>
<thead><tr><th>المريض</th><th>الفحص</th><th>الطلب</th><th>تاريخ الإنشاء</th><th></th></tr></thead>
<tbody>
@forelse($reports as $report)
<tr>
    <td>{{ $report->patient?->full_name }}</td>
    <td>{{ $report->result?->test?->name_ar }}</td>
    <td>{{ $report->order?->order_number }}</td>
    <td>{{ $report->generated_at }}</td>
    <td><a class="btn btn-ghost" href="{{ route('reports.download', $report) }}">تنزيل</a></td>
</tr>
@empty
<tr><td colspan="5">لا توجد تقارير بعد.</td></tr>
@endforelse
</tbody>
</table>
{{ $reports->links() }}
</div>
@endsection
