@extends('layouts.app')
@section('heading', 'لوحة المختبر')
@section('subheading', 'طوابير العمل الموحدة')
@section('content')
<div class="stats">
<div class="stat"><span class="muted">طلبات جديدة</span><strong>{{ $newOrders }}</strong></div>
<div class="stat"><span class="muted">قيد التحليل</span><strong>{{ $inAnalysis }}</strong></div>
<div class="stat"><span class="muted">بانتظار المراجعة</span><strong>{{ $needsReview }}</strong></div>
<div class="stat"><span class="muted">تنبيهات حرجة</span><strong>{{ $criticalOpen }}</strong></div>
</div>
<div class="stats">
<div class="stat"><span class="muted">جاهز</span><strong>{{ $ready }}</strong></div>
<div class="stat"><span class="muted">مؤجل</span><strong>{{ $deferred }}</strong></div>
<div class="stat"><span class="muted">مرفوض</span><strong>{{ $rejected }}</strong></div>
<div class="stat"><span class="muted">عينات/طلبات بانتظار</span><strong>{{ $awaitingSamples }}</strong></div>
</div>
<div class="card">
<div class="actions" style="margin-bottom:1rem">
<a class="btn" href="{{ route('lab.samples.reception') }}">استلام عينة</a>
<a class="btn btn-secondary" href="{{ route('lab.orders.index', ['status'=>'new']) }}">الطلبات الجديدة</a>
<a class="btn btn-secondary" href="{{ route('lab.results.entry.index') }}">إدخال النتائج</a>
<a class="btn btn-secondary" href="{{ route('lab.results.review.index') }}">مراجعة النتائج</a>
<a class="btn btn-ghost" href="{{ route('lab.critical.index') }}">التنبيهات الحرجة</a>
</div>
<table>
<thead><tr><th>الطلب</th><th>المريض</th><th>الحالة</th></tr></thead>
<tbody>
@foreach($recentOrders as $order)
<tr>
<td><a href="{{ route('lab.orders.show', $order) }}">{{ $order->order_number }}</a></td>
<td>{{ $order->patient->full_name }}</td>
<td><span class="badge">{{ \App\Models\TestOrder::statusLabel($order->status) }}</span></td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
