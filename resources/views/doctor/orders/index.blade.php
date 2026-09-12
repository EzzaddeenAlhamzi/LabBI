@extends('layouts.app')
@section('heading', 'طلباتي')
@section('content')
<div class="actions" style="margin-bottom:1rem"><a class="btn" href="{{ route('doctor.orders.create') }}">طلب جديد</a></div>
<div class="card"><table>
<thead><tr><th>رقم الطلب</th><th>المريض</th><th>الحالة</th><th>التاريخ</th></tr></thead>
<tbody>@foreach($orders as $order)
<tr><td><a href="{{ route('doctor.orders.show', $order) }}">{{ $order->order_number }}</a></td><td>{{ $order->patient->full_name }}</td>
<td>{{ \App\Models\TestOrder::statusLabel($order->status) }}</td><td>{{ $order->ordered_at->format('Y-m-d H:i') }}</td></tr>
@endforeach</tbody></table>{{ $orders->links() }}</div>
@endsection