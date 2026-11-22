@extends('layouts.admin')
@section('heading', 'إدخال نتائج الطلب')
@section('subheading', $order->order_number.' — '.$order->patient->full_name)
@section('page_actions')
    @if($order->status === 'sample_received')
        <form method="post" action="{{ route('lab.results.start_analysis', $order) }}">@csrf
            <button class="btn btn-secondary" type="submit"><x-icon name="play" /> بدء التحليل</button>
        </form>
    @endif
    <a class="btn btn-ghost" href="{{ route('lab.results.entry.index') }}"><x-icon name="chevron-right" /> القائمة</a>
@endsection
@section('content')
<div class="alert alert-info"><x-icon name="info" /><span>التصنيف يُحسب على الخادم عبر محرك القيم الحرجة فقط — لا إعادة حساب في المتصفح.</span></div>

@foreach($order->items as $item)
@php($result = $item->result)
@php($range = $item->test->referenceRanges->firstWhere('is_active', true))
<div class="card">
    <div class="card-title"><x-icon name="flask" /> {{ $item->test->code }} — {{ $item->test->name_ar }}</div>
    <p class="card-sub">
        الوحدة المتوقعة: <b dir="ltr">{{ $item->test->unit }}</b>
        @if($range)
            · طبيعي: <b dir="ltr">{{ $range->normal_low }}–{{ $range->normal_high }}</b>
            · حرج: <b dir="ltr">≤{{ $range->critical_low }} / ≥{{ $range->critical_high }}</b>
        @endif
    </p>
    @if($result)
    <div class="flex" style="margin-bottom:.75rem">
        <x-status-badge :status="$result->status" :label="\App\Models\Result::statusLabel($result->status)" />
        @php
            $ctone = match($result->classification) {
                'normal' => 'success',
                'critical','panic','critical_low','critical_high' => 'danger',
                'borderline','high','low' => 'warning',
                default => 'neutral',
            };
        @endphp
        <x-status-badge :tone="$ctone" :label="\App\Domain\Critical\ResultClassification::labelAr($result->classification)" />
    </div>
    @endif

    @if(!$result || $result->isEditable())
    <form method="post" action="{{ route('lab.results.save', $item) }}" class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; align-items:end">
        @csrf
        <div class="field">
            <label for="value_numeric_{{ $item->id }}">القيمة <span class="req">*</span></label>
            <input type="number" step="any" id="value_numeric_{{ $item->id }}" name="value_numeric" value="{{ old('value_numeric', $result?->value_numeric) }}" required>
        </div>
        <div class="field">
            <label for="unit_{{ $item->id }}">الوحدة</label>
            <input type="text" id="unit_{{ $item->id }}" name="unit" value="{{ old('unit', $result?->unit ?? $item->test->unit) }}">
        </div>
        <div class="field">
            <label for="notes_{{ $item->id }}">ملاحظات</label>
            <textarea id="notes_{{ $item->id }}" name="notes" rows="2">{{ old('notes', $result?->notes) }}</textarea>
        </div>
        <div class="form-actions" style="grid-column:1/-1">
            <button class="btn btn-ghost" type="submit" name="action" value="draft"><x-icon name="save" /> حفظ مسودة</button>
            <button class="btn" type="submit" name="action" value="submit"><x-icon name="send" /> إرسال للمراجعة</button>
        </div>
    </form>
    @else
    <p class="muted">النتيجة مقفلة للتعديل ({{ \App\Models\Result::statusLabel($result->status) }}).</p>
    @endif
</div>
@endforeach
@endsection
