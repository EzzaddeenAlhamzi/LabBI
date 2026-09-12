@extends('layouts.app')
@section('heading', 'تفاصيل تنبيه حرج')
@section('content')
<div class="card">
<p>المريض: {{ $alert->patient->full_name }} ({{ $alert->patient->medical_file_number }})</p>
<p>الطلب: {{ $alert->order->order_number }}</p>
<p>الفحص: {{ $alert->test->code }} — {{ $alert->test->name_ar }}</p>
<p>النتيجة: {{ $alert->detected_value }} {{ $alert->unit }}</p>
<p>وقت الاكتشاف: <strong>{{ $alert->discovered_at }}</strong></p>
<p>الحالة: {{ $alert->status }}</p>
@if($alert->threshold_snapshot)
<pre style="white-space:pre-wrap;background:#f7faf8;padding:0.75rem;border-radius:0.5rem">{{ json_encode($alert->threshold_snapshot, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
@endif
@if($alert->status === 'open')
<form method="post" action="{{ route('lab.critical.acknowledge', $alert) }}">@csrf
<button class="btn" type="submit">تأكيد الاطلاع</button>
</form>
@endif
<p><a href="{{ route('lab.results.review.show', $alert->result) }}">فتح النتيجة للمراجعة</a></p>
</div>
@endsection
