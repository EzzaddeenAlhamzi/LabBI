@extends('layouts.admin')
@section('heading', 'تفاصيل نتيجة')
@section('subheading', $result->test->name_ar.' — '.$result->patient->full_name)
@section('page_actions')
    <a class="btn btn-ghost" href="{{ route('doctor.results.index') }}"><x-icon name="chevron-right" /> النتائج</a>
@endsection
@section('content')
@php
$ctone = match($result->classification) {
    'normal' => 'success',
    'critical','panic','critical_low','critical_high' => 'danger',
    'borderline','high','low' => 'warning',
    default => 'neutral',
};
@endphp
@if($result->classification === 'critical' || $result->criticalAlert)
<div class="alert alert-error"><x-icon name="alert-octagon" /><span><b>نتيجة حرجة</b> — قيمة تتجاوز العتبات الحرجة وتتطلب متابعة.</span></div>
@endif

<div class="card">
    <div class="card-title"><x-icon name="flask-conical" /> القيمة المخبرية</div>
    <div class="dl">
        <div class="item"><div class="k">المريض</div><div class="v">{{ $result->patient->full_name }} <span class="muted-cell small" dir="ltr">{{ $result->patient->medical_file_number }}</span></div></div>
        <div class="item"><div class="k">الطلب</div><div class="v" dir="ltr">{{ $result->order->order_number }}</div></div>
        <div class="item"><div class="k">الفحص</div><div class="v">{{ $result->test->code }} — {{ $result->test->name_ar }}</div></div>
        <div class="item"><div class="k">القيمة</div><div class="v" style="font-size:1.25rem"><b dir="ltr">{{ $result->value_numeric }} {{ $result->unit }}</b></div></div>
        <div class="item"><div class="k">التصنيف</div><div class="v"><x-status-badge :tone="$ctone" :label="\App\Domain\Critical\ResultClassification::labelAr($result->classification)" /></div></div>
        <div class="item"><div class="k">حالة النتيجة</div><div class="v"><x-status-badge :status="$result->status" :label="\App\Models\Result::statusLabel($result->status)" /></div></div>
        @if($result->approved_at)
        <div class="item"><div class="k">تاريخ الاعتماد</div><div class="v" dir="ltr">{{ $result->approved_at }}</div></div>
        @endif
        @if($result->range_snapshot)
        <div class="item"><div class="k">المدى المرجعي</div><div class="v muted-cell" dir="ltr">طبيعي {{ $result->range_snapshot['normal_low'] ?? '—' }}–{{ $result->range_snapshot['normal_high'] ?? '—' }} | حرج {{ $result->range_snapshot['critical_low'] ?? '—' }} / {{ $result->range_snapshot['critical_high'] ?? '—' }}</div></div>
        @endif
        @if($result->criticalAlert)
        <div class="item"><div class="k">تنبيه حرج</div><div class="v">اكتشف في <span dir="ltr">{{ $result->criticalAlert->discovered_at }}</span>@if($result->criticalAlert->notified_at) — أُشعر الطبيب في <span dir="ltr">{{ $result->criticalAlert->notified_at }}</span>@endif</div></div>
        @endif
    </div>
    @if($result->attachments->isNotEmpty())
    <div style="margin-top:.9rem">
        <div class="k small muted" style="margin-bottom:.4rem">المرفقات</div>
        <ul class="small" style="margin:0;padding-inline-start:1.2rem">
            @foreach($result->attachments as $a)<li>{{ $a->original_name }} <span class="muted-cell">({{ $a->mime_type }})</span></li>@endforeach
        </ul>
    </div>
    @endif
    @if($report)
    <div style="margin-top:1rem"><a class="btn" href="{{ route('reports.download', $report) }}"><x-icon name="download" /> تنزيل تقرير PDF</a></div>
    @elseif($result->status === 'approved')
    <p class="muted small" style="margin-top:.9rem">التقرير قيد التوليد أو غير متاح بعد.</p>
    @endif
</div>
@endsection
