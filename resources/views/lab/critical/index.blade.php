@extends('layouts.app')
@section('heading', 'التنبيهات الحرجة')
@section('subheading', 'داخل النظام فقط — بدون واتساب أو بريد في المرحلة 2')
@section('content')
<div class="card">
<table>
<thead><tr><th>الاكتشاف</th><th>المريض</th><th>الطلب</th><th>الفحص</th><th>القيمة</th><th>العتبات</th><th>الحالة</th><th></th></tr></thead>
<tbody>
@forelse($alerts as $alert)
<tr>
<td>{{ $alert->discovered_at }}</td>
<td>{{ $alert->patient->full_name }}</td>
<td>{{ $alert->order->order_number }}</td>
<td>{{ $alert->test->name_ar }}</td>
<td>{{ $alert->detected_value }} {{ $alert->unit }}</td>
<td class="muted" style="font-size:0.85rem">
CL {{ $alert->threshold_snapshot['critical_low'] ?? '—' }} /
CH {{ $alert->threshold_snapshot['critical_high'] ?? '—' }}
</td>
<td>{{ $alert->status }}</td>
<td><a href="{{ route('lab.critical.show', $alert) }}">تفاصيل</a></td>
</tr>
@empty
<tr><td colspan="8">لا توجد تنبيهات حرجة.</td></tr>
@endforelse
</tbody>
</table>
{{ $alerts->links() }}
</div>
@endsection
