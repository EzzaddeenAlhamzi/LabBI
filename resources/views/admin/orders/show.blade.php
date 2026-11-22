@extends('layouts.admin')
@section('title', 'تفاصيل الطلب')
@section('heading', 'طلب فحص '.$order->order_number)
@section('subheading', 'تم الطلب: '.$order->ordered_at->format('Y-m-d H:i'))
@section('page_actions')
    <a class="btn btn-secondary" href="{{ route('admin.orders.index') }}"><x-icon name="chevron-right" /> رجوع للطلبات</a>
@endsection
@section('content')
<div class="grid-3" style="align-items:start">
    <div class="card">
        <div class="card-title"><x-icon name="info" /> معلومات الطلب</div>
        <div class="dl">
            <div class="item"><div class="k">رقم الطلب</div><div class="v"><code>{{ $order->order_number }}</code></div></div>
            <div class="item"><div class="k">الحالة</div><div class="v"><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></div></div>
            <div class="item"><div class="k">تاريخ الطلب</div><div class="v">{{ $order->ordered_at->format('Y-m-d H:i') }}</div></div>
            <div class="item"><div class="k">القسم</div><div class="v">{{ $order->department?->name_ar ?? '—' }}</div></div>
        </div>
    </div>
    <div class="card">
        <div class="card-title"><x-icon name="patients" /> المريض</div>
        <div class="dl">
            <div class="item"><div class="k">الاسم</div><div class="v">{{ $order->patient->full_name }}</div></div>
            <div class="item"><div class="k">الرقم الطبي</div><div class="v"><code>{{ $order->patient->medical_file_number }}</code></div></div>
            <div class="item"><div class="k">الهاتف</div><div class="v" dir="ltr" style="text-align:start">{{ $order->patient->phone ?: '—' }}</div></div>
        </div>
        <div class="mt-1"><a class="btn btn-ghost btn-sm" href="{{ route('admin.patients.show', $order->patient) }}"><x-icon name="eye" /> ملف المريض</a></div>
    </div>
    <div class="card">
        <div class="card-title"><x-icon name="doctor" /> الطبيب</div>
        <div class="dl">
            <div class="item"><div class="k">الاسم</div><div class="v">{{ $order->doctor->user->name }}</div></div>
            <div class="item"><div class="k">التخصص</div><div class="v">{{ $order->doctor->specialty ?: '—' }}</div></div>
        </div>
    </div>
</div>

@if($order->notes)
<div class="card">
    <div class="card-title"><x-icon name="file-text" /> ملاحظات الطلب</div>
    <p class="mb-0">{{ $order->notes }}</p>
</div>
@endif

<div class="card flush">
    <div class="card-title" style="padding:1rem 1rem 0"><x-icon name="test-tubes" /> عناصر الطلب ({{ $order->items->count() }})</div>
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الفحص</th><th>الرمز</th><th>الوحدة</th><th>القسم</th><th>الحالة</th></tr></thead>
    <tbody>
    @forelse($order->items as $item)
    <tr>
        <td><b>{{ $item->test->name_ar }}</b></td>
        <td><code>{{ $item->test->code }}</code></td>
        <td><span class="badge badge-neutral">{{ $item->test->unit ?: '—' }}</span></td>
        <td>{{ $item->test->department?->name_ar ?: '—' }}</td>
        <td><x-status-badge :status="$item->status" :label="\App\Models\TestOrder::statusLabel($item->status)" /></td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><h3>لا توجد عناصر</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

<div class="card flush">
    <div class="card-title" style="padding:1rem 1rem 0"><x-icon name="test-tube" /> العينات ({{ $order->samples->count() }})</div>
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>رقم العينة</th><th>الحالة</th><th>وقت الاستلام</th><th>المستلِم</th></tr></thead>
    <tbody>
    @forelse($order->samples as $sample)
    <tr>
        <td><code>{{ $sample->sample_number }}</code></td>
        <td><x-status-badge :status="$sample->status" :label="\App\Models\Sample::statusLabel($sample->status)" /></td>
        <td class="muted-cell">{{ optional($sample->received_at)->format('Y-m-d H:i') ?: '—' }}</td>
        <td>{{ $sample->receiver?->name ?? '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="4"><div class="empty"><h3>لا توجد عينات بعد</h3><p>لم يتم استلام أي عينة لهذا الطلب.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
@endsection
