@extends('layouts.admin')
@section('heading', 'حالة الفحص')
@section('subheading', $result->test->name_ar)
@section('page_actions')
    <a class="btn btn-ghost" href="{{ route('patient.tests.index') }}"><x-icon name="chevron-right" /> فحوصاتي</a>
@endsection
@section('content')
<div class="card">
    <div class="card-title"><x-icon name="clock" /> {{ $result->test->name_ar }}</div>
    <div class="dl">
        <div class="item"><div class="k">الطلب</div><div class="v" dir="ltr">{{ $result->order->order_number }} — {{ \App\Models\TestOrder::statusLabel($result->order->status) }}</div></div>
        <div class="item"><div class="k">حالة النتيجة</div><div class="v"><x-status-badge :status="$result->status" :label="\App\Models\Result::statusLabel($result->status)" /></div></div>
    </div>
    <p class="muted" style="margin-top:.9rem">التفاصيل الرقمية الكاملة تظهر بعد اعتماد النتيجة.</p>
</div>
@endsection
