@extends('layouts.app')
@section('heading', 'إدخال النتائج')
@section('subheading', 'اختر طلباً لإدخال أو تعديل النتائج قبل الاعتماد')
@section('content')
<div class="card">
<table>
<thead><tr><th>الطلب</th><th>المريض</th><th>الحالة</th><th></th></tr></thead>
<tbody>
@forelse($orders as $order)
<tr>
<td>{{ $order->order_number }}</td>
<td>{{ $order->patient->full_name }}</td>
<td>{{ \App\Models\TestOrder::statusLabel($order->status) }}</td>
<td><a class="btn" href="{{ route('lab.results.entry', $order) }}">فتح</a></td>
</tr>
@empty
<tr><td colspan="4">لا توجد طلبات جاهزة لإدخال النتائج.</td></tr>
@endforelse
</tbody>
</table>
{{ $orders->links() }}
</div>
@endsection
