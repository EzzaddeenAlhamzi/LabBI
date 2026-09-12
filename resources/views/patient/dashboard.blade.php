@extends('layouts.app')
@section('heading', 'لوحة المريض')
@section('content')
<div class="card">
<p>مرحباً {{ $patient->full_name }}</p>
<p>الرقم الطبي: <strong>{{ $patient->medical_file_number }}</strong></p>
<p>الطبيب المعالج: {{ $patient->primaryDoctor?->user?->name ?: '—' }}</p>
</div>
<div class="card">
<h3>طلباتي</h3>
<table><thead><tr><th>الطلب</th><th>الحالة</th><th>التاريخ</th></tr></thead>
<tbody>@forelse($patient->orders as $order)
<tr><td>{{ $order->order_number }}</td><td>{{ \App\Models\TestOrder::statusLabel($order->status) }}</td><td>{{ $order->ordered_at->format('Y-m-d') }}</td></tr>
@empty<tr><td colspan="3">لا توجد طلبات بعد</td></tr>@endforelse</tbody></table>
</div>
@endsection