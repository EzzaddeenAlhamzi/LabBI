@extends('layouts.admin')
@section('heading', 'تفاصيل طلب المختبر')
@section('subheading', $order->order_number)
@section('page_actions')
    <a class="btn" href="{{ route('lab.samples.reception') }}?order={{ $order->id }}"><x-icon name="test-tube" /> استلام عينة</a>
    @if(in_array($order->status, ['sample_received','in_analysis','pending_review','deferred'], true))
        <a class="btn btn-secondary" href="{{ route('lab.results.entry', $order) }}"><x-icon name="flask" /> إدخال النتائج</a>
    @endif
    <a class="btn btn-ghost" href="{{ route('lab.orders.index') }}"><x-icon name="chevron-right" /> العودة</a>
@endsection
@section('content')
<div class="card">
    <div class="dl">
        <div class="item"><div class="k">المريض</div><div class="v">{{ $order->patient->full_name }} <span class="muted-cell small" dir="ltr">{{ $order->patient->medical_file_number }}</span></div></div>
        <div class="item"><div class="k">الطبيب الطالب</div><div class="v">{{ $order->doctor->user->name }}</div></div>
        <div class="item"><div class="k">تاريخ الطلب</div><div class="v" dir="ltr">{{ $order->ordered_at?->format('Y-m-d H:i') }}</div></div>
        <div class="item"><div class="k">الحالة</div><div class="v"><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></div></div>
        <div class="item"><div class="k">الفحوصات</div><div class="v">{{ $order->items->count() }}</div></div>
        <div class="item"><div class="k">العينات المسجلة</div><div class="v">{{ $order->samples->count() }}</div></div>
    </div>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الرمز</th><th>الفحص</th><th>حالة العنصر</th><th>النتيجة</th><th>التصنيف</th></tr></thead>
    <tbody>
    @foreach($order->items as $item)
    <tr>
        <td dir="ltr">{{ $item->test->code }}</td>
        <td>{{ $item->test->name_ar }}</td>
        <td class="muted-cell">{{ $item->status }}</td>
        <td dir="ltr">{{ $item->result?->value_numeric ?? '—' }} {{ $item->result?->unit }}</td>
        <td>
            @if($item->result)
                @php
                    $ctone = match($item->result->classification) {
                        'normal' => 'success',
                        'critical','panic','critical_low','critical_high' => 'danger',
                        'borderline','high','low' => 'warning',
                        default => 'neutral',
                    };
                @endphp
                <x-status-badge :tone="$ctone" :label="\App\Domain\Critical\ResultClassification::labelAr($item->result->classification)" />
            @else — @endif
        </td>
    </tr>
    @endforeach
    </tbody>
    </table>
    </div>
</div>

@if($order->samples->isNotEmpty())
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>رقم العينة</th><th>الحالة</th><th>تاريخ الاستلام</th><th>استلمها</th></tr></thead>
    <tbody>
    @foreach($order->samples as $sample)
    <tr>
        <td dir="ltr"><b>{{ $sample->sample_number }}</b></td>
        <td><x-status-badge :status="$sample->status" :label="\App\Models\Sample::statusLabel($sample->status)" /></td>
        <td class="muted-cell" dir="ltr">{{ $sample->received_at?->format('Y-m-d H:i') ?? '—' }}</td>
        <td class="muted-cell">{{ $sample->receiver?->name ?? '—' }}</td>
    </tr>
    @endforeach
    </tbody>
    </table>
    </div>
</div>
@endif
@endsection
