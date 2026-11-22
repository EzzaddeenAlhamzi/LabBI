@extends('layouts.admin')
@section('heading', 'تفاصيل نتيجتي')
@section('subheading', $result->test->name_ar)
@section('page_actions')
    <a class="btn btn-ghost" href="{{ route('patient.tests.index') }}"><x-icon name="chevron-right" /> فحوصاتي</a>
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
<div class="card">
    <div class="card-title"><x-icon name="flask-conical" /> {{ $result->test->name_ar }}</div>
    <div class="dl">
        <div class="item"><div class="k">الطلب</div><div class="v" dir="ltr">{{ $result->order->order_number }}</div></div>
        <div class="item"><div class="k">الطبيب</div><div class="v">{{ $result->order->doctor->user->name }}</div></div>
        <div class="item"><div class="k">القيمة</div><div class="v" style="font-size:1.5rem"><b dir="ltr">{{ $result->value_numeric }} {{ $result->unit }}</b></div></div>
        <div class="item"><div class="k">التصنيف</div><div class="v"><x-status-badge :tone="$ctone" :label="\App\Domain\Critical\ResultClassification::labelAr($result->classification)" /></div></div>
        <div class="item"><div class="k">تاريخ الاعتماد</div><div class="v" dir="ltr">{{ $result->approved_at }}</div></div>
        @if($result->range_snapshot)
        <div class="item"><div class="k">المدى المرجعي</div><div class="v muted-cell" dir="ltr">طبيعي {{ $result->range_snapshot['normal_low'] ?? '—' }}–{{ $result->range_snapshot['normal_high'] ?? '—' }}</div></div>
        @endif
    </div>
    @if($result->attachments->isNotEmpty())
    <div style="margin-top:.9rem">
        <div class="k small muted" style="margin-bottom:.4rem">المرفقات</div>
        <ul class="small" style="margin:0;padding-inline-start:1.2rem">
            @foreach($result->attachments as $a)<li>{{ $a->original_name }}</li>@endforeach
        </ul>
    </div>
    @endif
    @if($report)
    <div style="margin-top:1rem"><a class="btn" href="{{ route('reports.download', $report) }}"><x-icon name="download" /> تنزيل تقرير PDF</a></div>
    @endif
</div>
@endsection
