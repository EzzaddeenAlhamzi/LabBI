@extends('layouts.app')
@section('heading', 'تفاصيل نتيجتي')
@section('content')
<div class="card">
<p>الفحص: {{ $result->test->name_ar }}</p>
<p>الطلب: {{ $result->order->order_number }}</p>
<p>القيمة: <strong>{{ $result->value_numeric }} {{ $result->unit }}</strong></p>
<p>التصنيف: {{ \App\Domain\Critical\ResultClassification::labelAr($result->classification) }}</p>
<p>تاريخ الاعتماد: {{ $result->approved_at }}</p>
<p>الطبيب: {{ $result->order->doctor->user->name }}</p>
@if($result->attachments->isNotEmpty())
<h3>مرفقات</h3>
<ul>@foreach($result->attachments as $a)<li>{{ $a->original_name }}</li>@endforeach</ul>
@endif
@if($report)
<p class="actions" style="margin-top:1rem">
    <a class="btn" href="{{ route('reports.download', $report) }}">تنزيل تقرير PDF</a>
</p>
@endif
</div>
<p><a href="{{ route('patient.tests.index') }}">العودة لفحوصاتي</a></p>
@endsection
