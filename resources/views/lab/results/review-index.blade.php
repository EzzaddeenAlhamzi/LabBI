@extends('layouts.app')
@section('heading', 'مراجعة النتائج')
@section('subheading', 'قائمة انتظار الاعتماد — يتطلب صلاحية results.approve للاعتماد')
@section('content')
<div class="card">
<table>
<thead><tr><th>الطلب</th><th>المريض</th><th>الفحص</th><th>القيمة</th><th>التصنيف</th><th>أدخلها</th><th></th></tr></thead>
<tbody>
@forelse($results as $result)
<tr>
<td>{{ $result->order->order_number }}</td>
<td>{{ $result->patient->full_name }}</td>
<td>{{ $result->test->name_ar }}</td>
<td>{{ $result->value_numeric }} {{ $result->unit }}</td>
<td><span class="badge">{{ \App\Domain\Critical\ResultClassification::labelAr($result->classification) }}</span></td>
<td>{{ $result->enteredBy?->name }}</td>
<td><a class="btn" href="{{ route('lab.results.review.show', $result) }}">مراجعة</a></td>
</tr>
@empty
<tr><td colspan="7">لا توجد نتائج بانتظار المراجعة.</td></tr>
@endforelse
</tbody>
</table>
{{ $results->links() }}
</div>
@endsection
