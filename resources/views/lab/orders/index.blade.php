@extends('layouts.app')
@section('heading', 'طلبات المختبر')
@section('content')
<div class="card"><table>
<thead><tr><th>رقم الطلب</th><th>المريض</th><th>الطبيب</th><th>الحالة</th><th></th></tr></thead>
<tbody>@foreach($orders as $order)
<tr>
<td>{{ $order->order_number }}</td>
<td>{{ $order->patient->full_name }}</td>
<td>{{ $order->doctor->user->name }}</td>
<td>{{ \App\Models\TestOrder::statusLabel($order->status) }}</td>
<td><a href="{{ route('lab.orders.show', $order) }}">فتح</a></td>
</tr>
@endforeach</tbody></table>{{ $orders->links() }}</div>
@endsection