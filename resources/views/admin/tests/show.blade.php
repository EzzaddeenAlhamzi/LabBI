@extends('layouts.app')
@section('heading', 'تفاصيل الفحص')
@section('content')
<div class="card">
<p><strong>{{ $test->code }}</strong> — {{ $test->name_ar }}</p>
<p>الوحدة: {{ $test->unit ?: '—' }}</p>
<p>القسم: {{ $test->department?->name_ar ?: '—' }}</p>
</div>
<div class="card">
<h3>المديات المرجعية والحرجة</h3>
<table><thead><tr><th>طبيعي من</th><th>طبيعي إلى</th><th>حرج من</th><th>حرج إلى</th><th>نشط</th></tr></thead>
<tbody>@forelse($test->referenceRanges as $range)
<tr><td>{{ $range->normal_low }}</td><td>{{ $range->normal_high }}</td><td>{{ $range->critical_low }}</td><td>{{ $range->critical_high }}</td><td>{{ $range->is_active ? 'نعم' : 'لا' }}</td></tr>
@empty<tr><td colspan="5">لا توجد مديات — أضفها من شاشة المديات</td></tr>@endforelse</tbody></table>
<a class="btn" href="{{ route('admin.ranges.index') }}">إدارة المديات</a>
</div>
@endsection