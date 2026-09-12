@extends('layouts.app')
@section('heading', 'لوحة الطبيب')
@section('content')
<div class="stats">
<div class="stat"><span class="muted">طلباتي</span><strong>{{ $ordersCount }}</strong></div>
<div class="stat"><span class="muted">مرضاي</span><strong>{{ $patientsCount }}</strong></div>
</div>
<div class="card">
<a class="btn" href="{{ route('doctor.orders.create') }}">إنشاء طلب فحص</a>
<table style="margin-top:1rem">
<thead><tr><th>الطلب</th><th>المريض</th><th>الحالة</th></tr></thead>
<tbody>@foreach($recentOrders as $order)
<tr><td><a href="{{ route('doctor.orders.show', $order) }}">{{ $order->order_number }}</a></td><td>{{ $order->patient->full_name }}</td><td>{{ \App\Models\TestOrder::statusLabel($order->status) }}</td></tr>
@endforeach</tbody></table>
</div>
@endsection