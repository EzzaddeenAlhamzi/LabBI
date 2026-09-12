@extends('layouts.app')
@section('heading', 'النتائج الحرجة')
@section('subheading', 'تنبيهات داخل النظام فقط')
@section('content')
<div class="card">
<table>
<thead><tr><th>الاكتشاف</th><th>المريض</th><th>الفحص</th><th>القيمة</th><th>العتبات</th><th>الحالة</th><th></th></tr></thead>
<tbody>
@forelse($alerts as $alert)
<tr>
<td>{{ $alert->discovered_at }}</td>
<td>{{ $alert->patient->full_name }}</td>
<td>{{ $alert->test->name_ar }}</td>
<td>{{ $alert->detected_value }} {{ $alert->unit }}</td>
<td>CL {{ $alert->threshold_snapshot['critical_low'] ?? '—' }} / CH {{ $alert->threshold_snapshot['critical_high'] ?? '—' }}</td>
<td>{{ $alert->status }}</td>
<td><a href="{{ route('doctor.results.show', $alert->result) }}">النتيجة</a></td>
</tr>
@empty
<tr><td colspan="7">لا تنبيهات حرجة.</td></tr>
@endforelse
</tbody>
</table>
{{ $alerts->links() }}
</div>
@endsection
