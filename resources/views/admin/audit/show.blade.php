@extends('layouts.admin')
@section('title', 'تفاصيل سجل عملية')
@section('heading', 'تفاصيل سجل العملية #'.$log->id)
@section('subheading', 'عرض آمن للبيانات الوصفية — القيم الحساسة محجوبة')
@section('page_actions')
    <a class="btn btn-secondary" href="{{ route('admin.audit.index') }}"><x-icon name="chevron-right" /> رجوع للسجل</a>
@endsection
@section('content')
<div class="card">
    <div class="card-title"><x-icon name="scroll" /> بيانات العملية</div>
    <div class="dl">
        <div class="item"><div class="k">الإجراء</div><div class="v"><code>{{ $log->action }}</code></div></div>
        <div class="item"><div class="k">المستخدم</div><div class="v">{{ $log->user?->name ?? '—' }}@if($log->user) <span class="muted small">({{ $log->user->email }})</span>@endif</div></div>
        <div class="item"><div class="k">الوقت</div><div class="v" dir="ltr" style="text-align:start">{{ $log->created_at }}</div></div>
        <div class="item"><div class="k">IP</div><div class="v" dir="ltr" style="text-align:start">{{ $log->ip_address ?: '—' }}</div></div>
        <div class="item"><div class="k">الكيان</div><div class="v">@if($log->auditable_type){{ $log->auditable_type }} #{{ $log->auditable_id }}@else — @endif</div></div>
    </div>
</div>
<div class="grid-2" style="align-items:start">
    <div class="card">
        <div class="card-title"><x-icon name="arrow-down" /> القيم السابقة</div>
        <pre class="json">{{ json_encode($metadata['old'] ?? null, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    <div class="card">
        <div class="card-title"><x-icon name="arrow-up" /> القيم الجديدة</div>
        <pre class="json">{{ json_encode($metadata['new'] ?? null, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</div>
@endsection
