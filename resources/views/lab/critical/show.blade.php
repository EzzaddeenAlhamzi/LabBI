@extends('layouts.admin')
@section('heading', 'تفاصيل تنبيه حرج')
@section('subheading', $alert->order->order_number.' — '.$alert->test->name_ar)
@section('page_actions')
    <a class="btn btn-danger" href="{{ route('lab.results.review.show', $alert->result) }}"><x-icon name="eye" /> فتح النتيجة للمراجعة</a>
    <a class="btn btn-ghost" href="{{ route('lab.critical.index') }}"><x-icon name="chevron-right" /> التنبيهات</a>
@endsection
@section('content')
<div class="alert alert-error"><x-icon name="alert-octagon" /><span><b>قيمة حرجة مكتشفة</b> — يتطلب هذا التنبيه إقراراً ومتابعة فورية وفق بروتوكول المختبر.</span></div>

<div class="card">
    <div class="card-title"><x-icon name="alert-octagon" /> بيانات التنبيه</div>
    <div class="dl">
        <div class="item"><div class="k">المريض</div><div class="v">{{ $alert->patient->full_name }} <span class="muted-cell small" dir="ltr">({{ $alert->patient->medical_file_number }})</span></div></div>
        <div class="item"><div class="k">الطلب</div><div class="v" dir="ltr">{{ $alert->order->order_number }}</div></div>
        <div class="item"><div class="k">الفحص</div><div class="v">{{ $alert->test->code }} — {{ $alert->test->name_ar }}</div></div>
        <div class="item"><div class="k">القيمة المكتشفة</div><div class="v" style="font-size:1.1rem"><b dir="ltr">{{ $alert->detected_value }} {{ $alert->unit }}</b></div></div>
        <div class="item"><div class="k">وقت الاكتشاف</div><div class="v" dir="ltr">{{ $alert->discovered_at }}</div></div>
        <div class="item"><div class="k">الحالة</div><div class="v"><x-status-badge :status="$alert->status" :label="$alert->status==='open'?'مفتوح':($alert->status==='acknowledged'?'تم الإقرار':'مغلق')" /></div></div>
        @if($alert->acknowledged_at)
        <div class="item"><div class="k">تم الإقرار</div><div class="v" dir="ltr">{{ $alert->acknowledged_at }}{{ $alert->acknowledged_by ? ' — '.$alert->acknowledged_by->name : '' }}</div></div>
        @endif
    </div>
</div>

@if($alert->threshold_snapshot)
<div class="card">
    <div class="card-title"><x-icon name="ruler" /> لقطة العتبات</div>
    <pre class="mono" style="white-space:pre-wrap;background:var(--surface-2);padding:.75rem;border-radius:.5rem;font-size:.8rem;overflow-x:auto">{{ json_encode($alert->threshold_snapshot, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
</div>
@endif

@if($alert->status === 'open')
<div class="card">
    <div class="card-title"><x-icon name="check-circle" /> إقرار التنبيه</div>
    <form method="post" action="{{ route('lab.critical.acknowledge', $alert) }}">
        @csrf
        <div class="form-actions"><button class="btn" type="submit"><x-icon name="check" /> تأكيد الاطلاع</button></div>
    </form>
</div>
@endif
@endsection
