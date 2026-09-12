@extends('layouts.app')
@section('heading', 'طلب مختبر')
@section('content')
<div class="card">
<p>{{ $order->order_number }} — {{ \App\Models\TestOrder::statusLabel($order->status) }}</p>
<p>المريض: {{ $order->patient->full_name }} ({{ $order->patient->medical_file_number }})</p>
<p>الطبيب: {{ $order->doctor->user->name }}</p>
<a class="btn" href="{{ route('lab.samples.reception') }}?order={{ $order->id }}">استلام عينة لهذا الطلب</a>
</div>
<div class="card"><h3>الفحوصات</h3>
<ul>@foreach($order->items as $item)<li>{{ $item->test->code }} — {{ $item->test->name_ar }}</li>@endforeach</ul>
</div>
@endsection