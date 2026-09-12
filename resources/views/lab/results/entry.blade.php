@extends('layouts.app')
@section('heading', 'إدخال نتائج الطلب')
@section('subheading', $order->order_number.' — '.$order->patient->full_name)
@section('content')
<div class="card">
<p>الحالة: <span class="badge">{{ \App\Models\TestOrder::statusLabel($order->status) }}</span></p>
<p class="muted">التصنيف يُحسب على الخادم عبر محرك القيم الحرجة فقط — لا إعادة حساب في المتصفح.</p>
@if($order->status === 'sample_received')
<form method="post" action="{{ route('lab.results.start_analysis', $order) }}" style="margin-bottom:1rem">@csrf
<button class="btn btn-secondary" type="submit">بدء التحليل</button>
</form>
@endif
</div>

@foreach($order->items as $item)
@php($result = $item->result)
@php($range = $item->test->referenceRanges->firstWhere('is_active', true))
<div class="card">
<h3>{{ $item->test->code }} — {{ $item->test->name_ar }}</h3>
<p class="muted">الوحدة المتوقعة: {{ $item->test->unit }}
@if($range)
| طبيعي: {{ $range->normal_low }}–{{ $range->normal_high }}
| حرج: ≤{{ $range->critical_low }} / ≥{{ $range->critical_high }}
<span class="badge">بيانات تجريبية حتى CAT-04</span>
@endif
</p>
@if($result)
<p>الحالة الحالية: {{ \App\Models\Result::statusLabel($result->status) }} —
التصنيف: <strong class="@if($result->classification==='critical') text-critical @endif">{{ \App\Domain\Critical\ResultClassification::labelAr($result->classification) }}</strong>
</p>
@endif

@if(!$result || $result->isEditable())
<form method="post" action="{{ route('lab.results.save', $item) }}">
@csrf
<label>القيمة</label>
<input type="number" step="any" name="value_numeric" value="{{ old('value_numeric', $result?->value_numeric) }}" required>
<label>الوحدة</label>
<input type="text" name="unit" value="{{ old('unit', $result?->unit ?? $item->test->unit) }}">
<label>ملاحظات</label>
<textarea name="notes" rows="2">{{ old('notes', $result?->notes) }}</textarea>
<div class="actions">
<button class="btn btn-ghost" type="submit" name="action" value="draft">حفظ مسودة</button>
<button class="btn" type="submit" name="action" value="submit">إرسال للمراجعة</button>
</div>
</form>
@else
<p class="muted">النتيجة مقفلة للتعديل ({{ \App\Models\Result::statusLabel($result->status) }}).</p>
@endif
</div>
@endforeach

<p><a href="{{ route('lab.results.entry.index') }}">العودة للقائمة</a></p>
@endsection
@push('styles')
<style>.text-critical{color:var(--danger);font-weight:700}</style>
@endpush
