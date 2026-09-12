@extends('layouts.app')
@section('heading', 'الفحوصات المكتملة')
@section('content')
<div class="card">
<h3>طلبات جاهزة / مكتملة / مؤرشفة</h3>
<table>
<thead><tr><th>الطلب</th><th>المريض</th><th>الحالة</th></tr></thead>
<tbody>
@foreach($orders as $order)
<tr>
<td><a href="{{ route('lab.orders.show', $order) }}">{{ $order->order_number }}</a></td>
<td>{{ $order->patient->full_name }}</td>
<td>{{ \App\Models\TestOrder::statusLabel($order->status) }}</td>
</tr>
@endforeach
</tbody>
</table>
{{ $orders->links() }}
</div>
<div class="card">
<h3>أحدث النتائج المعتمدة</h3>
<table>
<thead><tr><th>المريض</th><th>الفحص</th><th>القيمة</th><th>التصنيف</th><th>الاعتماد</th></tr></thead>
<tbody>
@foreach($results as $result)
<tr>
<td>{{ $result->patient->full_name }}</td>
<td>{{ $result->test->name_ar }}</td>
<td>{{ $result->value_numeric }} {{ $result->unit }}</td>
<td>{{ \App\Domain\Critical\ResultClassification::labelAr($result->classification) }}</td>
<td>{{ $result->approved_at }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
