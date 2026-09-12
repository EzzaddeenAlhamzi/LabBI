@extends('layouts.app')
@section('heading', 'ملف المريض')
@section('content')
<div class="card">
<p><strong>الرقم الطبي:</strong> {{ $patient->medical_file_number }}</p>
<p><strong>الاسم:</strong> {{ $patient->full_name }}</p>
<p><strong>الهاتف:</strong> {{ $patient->phone ?: '—' }}</p>
<p><strong>الطبيب:</strong> {{ $patient->primaryDoctor?->user?->name ?: '—' }}</p>
<a class="btn" href="{{ route('admin.patients.edit', $patient) }}">تعديل</a>
</div>
<div class="card">
<h3>الطلبات المرتبطة</h3>
<table>
<thead><tr><th>رقم الطلب</th><th>الحالة</th><th>التاريخ</th></tr></thead>
<tbody>
@forelse($patient->orders as $order)
<tr>
<td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
<td>{{ \App\Models\TestOrder::statusLabel($order->status) }}</td>
<td>{{ $order->ordered_at->format('Y-m-d H:i') }}</td>
</tr>
@empty
<tr><td colspan="3">لا توجد طلبات</td></tr>
@endforelse
</tbody>
</table>
</div>
@endsection