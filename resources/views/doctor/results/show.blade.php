@extends('layouts.app')
@section('heading', 'تفاصيل نتيجة')
@section('content')
<div class="card">
<p>المريض: {{ $result->patient->full_name }}</p>
<p>الطلب: {{ $result->order->order_number }}</p>
<p>الفحص: {{ $result->test->name_ar }}</p>
<p>القيمة: <strong>{{ $result->value_numeric }} {{ $result->unit }}</strong></p>
<p>التصنيف: {{ \App\Domain\Critical\ResultClassification::labelAr($result->classification) }}</p>
<p>حالة النتيجة: {{ \App\Models\Result::statusLabel($result->status) }}</p>
@if($result->approved_at)<p>تاريخ الاعتماد: {{ $result->approved_at }}</p>@endif
@if($result->classification === 'critical' || $result->criticalAlert)
<p class="badge" style="background:#fdecec;color:var(--danger)">نتيجة حرجة</p>
@endif
@if($result->criticalAlert)
<p class="muted">تنبيه حرج — اكتشف في {{ $result->criticalAlert->discovered_at }}
@if($result->criticalAlert->notified_at) — أُشعر الطبيب في {{ $result->criticalAlert->notified_at }}@endif
</p>
@endif
@if($result->attachments->isNotEmpty())
<h3>مرفقات</h3>
<ul>@foreach($result->attachments as $a)<li>{{ $a->original_name }} ({{ $a->mime_type }})</li>@endforeach</ul>
@endif
@if($report)
<p class="actions" style="margin-top:1rem">
    <a class="btn" href="{{ route('reports.download', $report) }}">تنزيل تقرير PDF</a>
</p>
@elseif($result->status === 'approved')
<p class="muted">التقرير قيد التوليد أو غير متاح بعد.</p>
@endif
</div>
@endsection
