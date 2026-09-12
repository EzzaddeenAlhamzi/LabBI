@extends('layouts.app')
@section('heading', 'حالة الفحص')
@section('content')
<div class="card">
<p>الفحص: {{ $result->test->name_ar }}</p>
<p>الطلب: {{ $result->order->order_number }} — {{ \App\Models\TestOrder::statusLabel($result->order->status) }}</p>
<p>حالة النتيجة: <strong>{{ \App\Models\Result::statusLabel($result->status) }}</strong></p>
<p class="muted">التفاصيل الرقمية الكاملة تظهر بعد الاعتماد.</p>
</div>
<p><a href="{{ route('patient.tests.index') }}">العودة لفحوصاتي</a></p>
@endsection
