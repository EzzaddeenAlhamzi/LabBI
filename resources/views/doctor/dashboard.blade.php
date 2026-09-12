@extends('layouts.app')
@section('heading', 'لوحة الطبيب')
@section('content')
<div class="stats">
<div class="stat"><span class="muted">طلباتي</span><strong>{{ $ordersCount }}</strong></div>
<div class="stat"><span class="muted">مرضاي</span><strong>{{ $patientsCount }}</strong></div>
<div class="stat"><span class="muted">نتائج معتمدة</span><strong>{{ $resultsCount }}</strong></div>
<div class="stat"><span class="muted">تنبيهات حرجة مفتوحة</span><strong>{{ $criticalCount }}</strong></div>
</div>
<div class="card">
<div class="actions" style="margin-bottom:1rem">
<a class="btn" href="{{ route('doctor.orders.create') }}">إنشاء طلب فحص</a>
<a class="btn btn-secondary" href="{{ route('doctor.results.index') }}">النتائج</a>
<a class="btn btn-ghost" href="{{ route('doctor.critical.index') }}">النتائج الحرجة</a>
</div>
<table>
<thead><tr><th>الطلب</th><th>المريض</th><th>الحالة</th></tr></thead>
<tbody>@foreach($recentOrders as $order)
<tr><td><a href="{{ route('doctor.orders.show', $order) }}">{{ $order->order_number }}</a></td><td>{{ $order->patient->full_name }}</td><td>{{ \App\Models\TestOrder::statusLabel($order->status) }}</td></tr>
@endforeach</tbody></table>
</div>
@endsection
