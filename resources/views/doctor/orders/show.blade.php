@extends('layouts.app')
@section('heading', 'تفاصيل طلبي')
@section('content')
<div class="card">
<p>{{ $order->order_number }}</p>
<p>المريض: {{ $order->patient->full_name }}</p>
<p>الحالة: {{ \App\Models\TestOrder::statusLabel($order->status) }}</p>
<ul>@foreach($order->items as $item)<li>{{ $item->test->name_ar }}</li>@endforeach</ul>
</div>
@endsection