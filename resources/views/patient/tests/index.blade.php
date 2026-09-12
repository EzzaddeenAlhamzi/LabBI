@extends('layouts.app')
@section('heading', 'فحوصاتي')
@section('content')
<div class="card">
<table>
<thead><tr><th>الطلب</th><th>الحالة</th><th>الفحوصات</th><th>التاريخ</th></tr></thead>
<tbody>
@forelse($orders as $order)
<tr>
<td>{{ $order->order_number }}</td>
<td>{{ \App\Models\TestOrder::statusLabel($order->status) }}</td>
<td>
<ul style="margin:0;padding-right:1rem">
@foreach($order->items as $item)
<li>
{{ $item->test->name_ar }}
@if($item->result)
— <a href="{{ route('patient.results.show', $item->result) }}">
@if($item->result->status === 'approved')
عرض النتيجة
@else
حالة: {{ \App\Models\Result::statusLabel($item->result->status) }}
@endif
</a>
@else
— لم تُدخل بعد
@endif
</li>
@endforeach
</ul>
</td>
<td>{{ $order->ordered_at->format('Y-m-d') }}</td>
</tr>
@empty
<tr><td colspan="4">لا فحوصات بعد.</td></tr>
@endforelse
</tbody>
</table>
{{ $orders->links() }}
</div>
@endsection
