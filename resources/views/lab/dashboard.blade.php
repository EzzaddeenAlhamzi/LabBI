@extends('layouts.app')
@section('heading', 'لوحة المختبر')
@section('content')
<div class="stats">
<div class="stat"><span class="muted">طلبات جديدة</span><strong>{{ $newOrders }}</strong></div>
<div class="stat"><span class="muted">عينات بانتظار</span><strong>{{ $awaitingSamples }}</strong></div>
</div>
<div class="card">
<div class="actions" style="margin-bottom:1rem">
<a class="btn" href="{{ route('lab.samples.reception') }}">استلام عينة</a>
<a class="btn btn-secondary" href="{{ route('lab.orders.index', ['status'=>'new']) }}">الطلبات الجديدة</a>
</div>
<table><thead><tr><th>الطلب</th><th>المريض</th><th>الحالة</th></tr></thead>
<tbody>@foreach($recentOrders as $order)
<tr><td><a href="{{ route('lab.orders.show', $order) }}">{{ $order->order_number }}</a></td><td>{{ $order->patient->full_name }}</td><td>{{ \App\Models\TestOrder::statusLabel($order->status) }}</td></tr>
@endforeach</tbody></table>
</div>
@endsection