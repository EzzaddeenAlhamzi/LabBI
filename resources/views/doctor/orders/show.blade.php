@extends('layouts.admin')
@section('heading', 'تفاصيل طلبي')
@section('subheading', $order->order_number)
@section('page_actions')
    <a class="btn btn-ghost" href="{{ route('doctor.orders.index') }}"><x-icon name="chevron-right" /> طلباتي</a>
@endsection
@section('content')
<div class="card">
    <div class="dl">
        <div class="item"><div class="k">رقم الطلب</div><div class="v" dir="ltr">{{ $order->order_number }}</div></div>
        <div class="item"><div class="k">المريض</div><div class="v">{{ $order->patient->full_name }} <span class="muted-cell small" dir="ltr">{{ $order->patient->medical_file_number }}</span></div></div>
        <div class="item"><div class="k">الحالة</div><div class="v"><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></div></div>
        <div class="item"><div class="k">تاريخ الطلب</div><div class="v" dir="ltr">{{ $order->ordered_at?->format('Y-m-d H:i') }}</div></div>
        <div class="item"><div class="k">عدد الفحوصات</div><div class="v">{{ $order->items->count() }}</div></div>
        @if($order->notes)<div class="item"><div class="k">ملاحظات</div><div class="v">{{ $order->notes }}</div></div>@endif
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
        <td dir="ltr">{{ ($item->result && $item->result->status === 'approved') ? $item->result->value_numeric.' '.$item->result->unit : '—' }}</td>
        <td>
            @if($item->result && $item->result->status === 'approved')
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
@endsection
