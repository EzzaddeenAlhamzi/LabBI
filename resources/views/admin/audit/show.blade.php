@extends('layouts.app')
@section('heading', 'تفاصيل سجل عملية')
@section('subheading', 'عرض آمن للبيانات الوصفية — القيم الحساسة محجوبة')
@section('content')
<div class="card">
    <p><strong>الإجراء:</strong> {{ $log->action }}</p>
    <p><strong>المستخدم:</strong> {{ $log->user?->name ?? '—' }} ({{ $log->user?->email }})</p>
    <p><strong>الوقت:</strong> {{ $log->created_at }}</p>
    <p><strong>IP:</strong> {{ $log->ip_address }}</p>
    <p><strong>الكيان:</strong>
        @if($log->auditable_type)
            {{ $log->auditable_type }} #{{ $log->auditable_id }}
        @else
            —
        @endif
    </p>
</div>
<div class="grid-2">
    <div class="card">
        <h3>القيم السابقة</h3>
        <pre style="white-space:pre-wrap;direction:ltr;text-align:left;font-size:0.85rem">{{ json_encode($metadata['old'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    <div class="card">
        <h3>القيم الجديدة</h3>
        <pre style="white-space:pre-wrap;direction:ltr;text-align:left;font-size:0.85rem">{{ json_encode($metadata['new'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</div>
<a class="btn btn-ghost" href="{{ route('admin.audit.index') }}">رجوع</a>
@endsection
