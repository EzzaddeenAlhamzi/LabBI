@extends('layouts.app')
@section('heading', 'تفاصيل الطلب')
@section('content')
<div class="card">
<p><strong>رقم الطلب:</strong> {{ $order->order_number }}</p>
<p><strong>المريض:</strong> {{ $order->patient->full_name }} ({{ $order->patient->medical_file_number }})</p>
<p><strong>الطبيب:</strong> {{ $order->doctor->user->name }}</p>
<p><strong>الحالة:</strong> {{ \App\Models\TestOrder::statusLabel($order->status) }}</p>
<p><strong>التاريخ:</strong> {{ $order->ordered_at->format('Y-m-d H:i') }}</p>
</div>
<div class="card"><h3>عناصر الطلب</h3>
<table><thead><tr><th>الفحص</th><th>الحالة</th></tr></thead>
<tbody>@foreach($order->items as $item)
<tr><td>{{ $item->test->code }} — {{ $item->test->name_ar }}</td><td>{{ $item->status }}</td></tr>
@endforeach</tbody></table></div>
<div class="card"><h3>العينات</h3>
@forelse($order->samples as $sample)
<p>{{ $sample->sample_number }} — {{ \App\Models\Sample::statusLabel($sample->status) }}</p>
@empty<p>لا توجد عينات بعد</p>@endforelse
</div>
@endsection