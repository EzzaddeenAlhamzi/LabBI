@extends('layouts.app')
@section('heading', 'تفاصيل طلب المختبر')
@section('content')
<div class="card">
<p><strong>{{ $order->order_number }}</strong> — <span class="badge">{{ \App\Models\TestOrder::statusLabel($order->status) }}</span></p>
<p>المريض: {{ $order->patient->full_name }} ({{ $order->patient->medical_file_number }})</p>
<p>الطبيب: {{ $order->doctor->user->name }}</p>
<div class="actions">
<a class="btn" href="{{ route('lab.samples.reception') }}?order={{ $order->id }}">استلام عينة</a>
@if(in_array($order->status, ['sample_received','in_analysis','pending_review','deferred'], true))
<a class="btn btn-secondary" href="{{ route('lab.results.entry', $order) }}">إدخال النتائج</a>
@endif
</div>
</div>
<div class="card"><h3>الفحوصات</h3>
<table>
<thead><tr><th>الرمز</th><th>الفحص</th><th>حالة العنصر</th><th>النتيجة</th><th>التصنيف</th></tr></thead>
<tbody>
@foreach($order->items as $item)
<tr>
<td>{{ $item->test->code }}</td>
<td>{{ $item->test->name_ar }}</td>
<td>{{ $item->status }}</td>
<td>{{ $item->result?->value_numeric ?? '—' }} {{ $item->result?->unit }}</td>
<td>
@if($item->result)
<span class="badge">{{ \App\Domain\Critical\ResultClassification::labelAr($item->result->classification) }}</span>
@else — @endif
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@if($order->samples->isNotEmpty())
<div class="card"><h3>العينات</h3>
<ul>@foreach($order->samples as $sample)
<li>{{ $sample->sample_number }} — {{ $sample->status }} @if($sample->received_at) ({{ $sample->received_at }}) @endif</li>
@endforeach</ul>
</div>
@endif
@endsection
