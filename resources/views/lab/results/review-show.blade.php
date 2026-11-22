@extends('layouts.admin')
@section('heading', 'صفحة مراجعة نتيجة')
@section('subheading', $result->order->order_number.' — '.$result->patient->full_name)
@section('page_actions')
    <a class="btn btn-ghost" href="{{ route('lab.results.review.index') }}"><x-icon name="chevron-right" /> القائمة</a>
@endsection
@section('content')
@php
$ctone = match($result->classification) {
    'normal' => 'success',
    'critical','panic','critical_low','critical_high' => 'danger',
    'borderline','high','low' => 'warning',
    default => 'neutral',
};
@endphp
@if($result->classification === 'critical')
<div class="alert alert-error"><x-icon name="alert-octagon" /><span><b>نتيجة حرجة</b> — يتطلب هذا التصنيف متابعة فورية وفق بروتوكول المختبر.</span></div>
@endif

<div class="card">
    <div class="card-title"><x-icon name="flask" /> بيانات النتيجة</div>
    <div class="dl">
        <div class="item"><div class="k">الطلب</div><div class="v" dir="ltr">{{ $result->order->order_number }}</div></div>
        <div class="item"><div class="k">المريض</div><div class="v">{{ $result->patient->full_name }} <span class="muted-cell small" dir="ltr">({{ $result->patient->medical_file_number }})</span></div></div>
        <div class="item"><div class="k">الفحص</div><div class="v">{{ $result->test->code }} — {{ $result->test->name_ar }}</div></div>
        <div class="item"><div class="k">القيمة</div><div class="v" style="font-size:1.1rem"><b dir="ltr">{{ $result->value_numeric }} {{ $result->unit }}</b></div></div>
        <div class="item"><div class="k">التصنيف</div><div class="v"><x-status-badge :tone="$ctone" :label="\App\Domain\Critical\ResultClassification::labelAr($result->classification)" /></div></div>
        <div class="item"><div class="k">أدخلها</div><div class="v">{{ $result->enteredBy?->name }} <span class="muted-cell small" dir="ltr">@ {{ $result->entered_at }}</span></div></div>
        @if($result->notes)
        <div class="item"><div class="k">ملاحظات الفني</div><div class="v">{{ $result->notes }}</div></div>
        @endif
        @if($result->range_snapshot)
        <div class="item"><div class="k">لقطة المديات</div><div class="v muted-cell small" dir="ltr">طبيعي {{ $result->range_snapshot['normal_low'] ?? '—' }}–{{ $result->range_snapshot['normal_high'] ?? '—' }} | حرج {{ $result->range_snapshot['critical_low'] ?? '—' }} / {{ $result->range_snapshot['critical_high'] ?? '—' }}</div></div>
        @endif
    </div>
    @if($result->criticalAlert)
    <div style="margin-top:.9rem"><a class="btn btn-danger btn-sm" href="{{ route('lab.critical.show', $result->criticalAlert) }}"><x-icon name="alert-octagon" /> عرض تنبيه حرج مرتبط</a></div>
    @endif
</div>

@if(auth()->user()->hasPermission('results.approve'))
<div class="card">
    <div class="card-title"><x-icon name="check-circle" /> إجراء الاعتماد</div>
    <form method="post" action="{{ route('lab.results.approve', $result) }}" class="form-grid" style="grid-template-columns:1fr;align-items:end">
        @csrf
        <div class="field">
            <label for="approve_comment">ملاحظات المراجع (اختياري للاعتماد)</label>
            <textarea id="approve_comment" name="comment" rows="3"></textarea>
        </div>
        <div class="form-actions"><button class="btn" type="submit"><x-icon name="check" /> اعتماد</button></div>
    </form>
    <hr style="border:none;border-top:1px solid var(--line);margin:1rem 0">
    <form method="post" action="{{ route('lab.results.return', $result) }}" class="form-grid" style="grid-template-columns:1fr;align-items:end">
        @csrf
        <div class="field">
            <label for="return_comment">سبب الإعادة (مطلوب)</label>
            <textarea id="return_comment" name="comment" rows="2" required></textarea>
        </div>
        <div class="form-actions"><button class="btn btn-secondary" type="submit"><x-icon name="refresh" /> إعادة للتصحيح</button></div>
    </form>
    <hr style="border:none;border-top:1px solid var(--line);margin:1rem 0">
    <form method="post" action="{{ route('lab.results.reject', $result) }}" class="form-grid" style="grid-template-columns:1fr;align-items:end">
        @csrf
        <div class="field">
            <label for="reject_comment">سبب الرفض (مطلوب)</label>
            <textarea id="reject_comment" name="comment" rows="2" required></textarea>
        </div>
        <div class="form-actions"><button class="btn btn-danger" type="submit"><x-icon name="x" /> رفض</button></div>
    </form>
    <p class="muted small" style="margin-top:.75rem">الاعتماد عبر صلاحية results.approve فقط (ليس اسم الدور).</p>
</div>
@else
<div class="card"><p class="muted">يمكنك عرض قائمة المراجعة، لكن الاعتماد يتطلب صلاحية results.approve.</p></div>
@endif

@if($result->reviews->isNotEmpty())
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الوقت</th><th>الإجراء</th><th>المراجع</th><th>التعليق</th></tr></thead>
    <tbody>
    @foreach($result->reviews as $review)
    <tr>
        <td class="muted-cell" dir="ltr">{{ $review->reviewed_at }}</td>
        <td>{{ $review->action }}</td>
        <td>{{ $review->reviewer->name }}</td>
        <td>{{ $review->comment }}</td>
    </tr>
    @endforeach
    </tbody>
    </table>
    </div>
</div>
@endif
@endsection
