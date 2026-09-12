@extends('layouts.app')
@section('heading', 'طلبات الفحص')
@section('content')
<div class="card">
<form method="get" class="actions">
<input name="q" value="{{ request('q') }}" placeholder="رقم الطلب / المريض" style="max-width:280px;margin:0">
<button class="btn" type="submit">بحث</button>
<a class="btn" href="{{ route('admin.orders.create') }}">طلب جديد</a>
</form>
</div>
<div class="card"><table>
<thead><tr><th>رقم الطلب</th><th>المريض</th><th>الطبيب</th><th>الحالة</th><th>التاريخ</th></tr></thead>
<tbody>@foreach($orders as $order)
<tr>
<td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
<td>{{ $order->patient->full_name }}</td>
<td>{{ $order->doctor->user->name }}</td>
<td><span class="badge">{{ \App\Models\TestOrder::statusLabel($order->status) }}</span></td>
<td>{{ $order->ordered_at->format('Y-m-d H:i') }}</td>
</tr>
@endforeach</tbody></table>{{ $orders->links() }}</div>
@endsection