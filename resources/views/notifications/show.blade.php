@extends('layouts.app')
@section('title', 'تفاصيل إشعار')
@section('heading', $notification->title)
@section('content')
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
@endsection
