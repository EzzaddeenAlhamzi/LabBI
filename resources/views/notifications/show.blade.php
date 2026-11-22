@php($isAdmin = auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('lab_technician') || auth()->user()?->hasRole('doctor') || auth()->user()?->hasRole('patient'))
@extends($isAdmin ? 'layouts.admin' : 'layouts.app')
@section('title', 'تفاصيل إشعار')
@section('heading', $notification->title)
@section('content')
@if($isAdmin)
<div class="card" style="max-width:860px">
    <div class="flex" style="gap:.5rem;flex-wrap:wrap;margin-bottom:1rem">
        @if($notification->priority === 'critical')
            <x-status-badge status="open" label="حرج" />
        @else
            <x-status-badge tone="neutral" label="عادي" />
        @endif
        <span class="badge badge-neutral">{{ $notification->type }}</span>
        <span class="badge {{ $notification->is_read ? 'badge-success' : 'badge-warning' }}">{{ $notification->is_read ? 'مقروء' : 'غير مقروء' }}</span>
    </div>
    <p style="font-size:1rem">{{ $notification->message }}</p>
    <div class="dl mt-2">
        @if($notification->patient)<div class="item"><div class="k">المريض</div><div class="v">{{ $notification->patient->full_name }}</div></div>@endif
        @if($notification->test)<div class="item"><div class="k">الفحص</div><div class="v">{{ $notification->test->name_ar }}</div></div>@endif
        @if($notification->order)<div class="item"><div class="k">الطلب</div><div class="v"><code>{{ $notification->order->order_number }}</code></div></div>@endif
        <div class="item"><div class="k">أُنشئ</div><div class="v" dir="ltr" style="text-align:start">{{ $notification->created_at }}</div></div>
    </div>
    @if($notification->link_url)
        <div class="mt-2"><a class="btn" href="{{ url($notification->link_url) }}"><x-icon name="external" /> فتح الرابط المرتبط</a></div>
    @endif
</div>
@if($notification->deliveries->isNotEmpty())
<div class="card flush">
    <div class="card-title" style="padding:1rem 1rem 0"><x-icon name="truck" /> تتبع التوصيل</div>
    <div class="table-wrap">
    <table class="data">
        <thead><tr><th>القناة</th><th>المستلم</th><th>الحالة</th><th class="num">المحاولات</th><th>مرجع المزود</th><th>الخطأ</th></tr></thead>
        <tbody>
        @foreach($notification->deliveries as $d)
            <tr>
                <td><span class="badge badge-neutral">{{ $d->channel }}</span></td>
                <td dir="ltr" style="text-align:start">{{ $d->recipient }}</td>
                <td><x-status-badge :status="$d->status" :label="$d->status" /></td>
                <td class="num">{{ $d->attempts }}</td>
                <td class="muted-cell" dir="ltr" style="text-align:start">{{ $d->provider_ref ?: '—' }}</td>
                <td class="muted-cell">{{ $d->error_summary ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif
<p><a href="{{ route('notifications.index') }}">← العودة لصندوق الوارد</a></p>
@else
<div class="card">
    <p>
        @if($notification->priority === 'critical')
            <span class="badge" style="background:#fdecec;color:var(--danger)">حرج</span>
        @else
            <span class="badge">عادي</span>
        @endif
        <span class="badge">{{ $notification->type }}</span>
    </p>
    <p>{{ $notification->message }}</p>
    @if($notification->patient)<p>المريض: {{ $notification->patient->full_name }}</p>@endif
    @if($notification->test)<p>الفحص: {{ $notification->test->name_ar }}</p>@endif
    @if($notification->order)<p>الطلب: {{ $notification->order->order_number }}</p>@endif
    <p class="muted">أُنشئ: {{ $notification->created_at }}</p>
    @if($notification->link_url)
        <p><a class="btn" href="{{ url($notification->link_url) }}">فتح الرابط المرتبط</a></p>
    @endif
</div>
@if($notification->deliveries->isNotEmpty())
<div class="card">
    <h3>تتبع التوصيل</h3>
    <table>
        <thead><tr><th>القناة</th><th>المستلم</th><th>الحالة</th><th>المحاولات</th><th>مرجع</th><th>خطأ</th></tr></thead>
        <tbody>
        @foreach($notification->deliveries as $d)
            <tr>
                <td>{{ $d->channel }}</td>
                <td>{{ $d->recipient }}</td>
                <td>{{ $d->status }}</td>
                <td>{{ $d->attempts }}</td>
                <td>{{ $d->provider_ref }}</td>
                <td>{{ $d->error_summary }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<p><a href="{{ route('notifications.index') }}">العودة لصندوق الوارد</a></p>
@endif
@endsection
