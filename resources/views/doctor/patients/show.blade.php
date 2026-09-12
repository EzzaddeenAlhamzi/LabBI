@extends('layouts.app')
@section('heading', 'ملف مريض')
@section('content')
<div class="card">
<p>{{ $patient->full_name }} — {{ $patient->medical_file_number }}</p>
<a class="btn" href="{{ route('doctor.orders.create') }}">إنشاء طلب</a>
</div>
<div class="card"><h3>طلباتي لهذا المريض</h3>
<ul>@foreach($patient->orders as $order)<li><a href="{{ route('doctor.orders.show', $order) }}">{{ $order->order_number }}</a> — {{ \App\Models\TestOrder::statusLabel($order->status) }}</li>@endforeach</ul>
</div>
@endsection