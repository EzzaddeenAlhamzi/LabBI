@extends('layouts.app')
@section('heading', 'نتائج مرضاي')
@section('content')
<div class="card">
<table>
<thead><tr><th>الطلب</th><th>المريض</th><th>الفحص</th><th>القيمة</th><th>التصنيف</th><th>الحالة</th><th></th></tr></thead>
<tbody>
@forelse($results as $result)
<tr>
<td>{{ $result->order->order_number }}</td>
<td>{{ $result->patient->full_name }}</td>
<td>{{ $result->test->name_ar }}</td>
<td>{{ $result->value_numeric }} {{ $result->unit }}</td>
<td>{{ \App\Domain\Critical\ResultClassification::labelAr($result->classification) }}</td>
<td>{{ \App\Models\Result::statusLabel($result->status) }}</td>
<td><a href="{{ route('doctor.results.show', $result) }}">تفاصيل</a></td>
</tr>
@empty
<tr><td colspan="7">لا نتائج بعد.</td></tr>
@endforelse
</tbody>
</table>
{{ $results->links() }}
</div>
@endsection
