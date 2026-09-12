@extends('layouts.app')
@section('heading', 'صفحة مراجعة نتيجة')
@section('content')
<div class="card">
<p>الطلب: {{ $result->order->order_number }}</p>
<p>المريض: {{ $result->patient->full_name }} ({{ $result->patient->medical_file_number }})</p>
<p>الفحص: {{ $result->test->code }} — {{ $result->test->name_ar }}</p>
<p>القيمة: <strong>{{ $result->value_numeric }} {{ $result->unit }}</strong></p>
<p>التصنيف: <strong>{{ \App\Domain\Critical\ResultClassification::labelAr($result->classification) }}</strong>
@if($result->classification === 'critical')
<span class="badge" style="background:#fdecec;color:var(--danger)">حرج</span>
@endif
</p>
<p>أدخلها: {{ $result->enteredBy?->name }} @ {{ $result->entered_at }}</p>
@if($result->notes)<p>ملاحظات الفني: {{ $result->notes }}</p>@endif
@if($result->range_snapshot)
<p class="muted">لقطة المديات: طبيعي {{ $result->range_snapshot['normal_low'] ?? '—' }}–{{ $result->range_snapshot['normal_high'] ?? '—' }}
| حرج {{ $result->range_snapshot['critical_low'] ?? '—' }} / {{ $result->range_snapshot['critical_high'] ?? '—' }}</p>
@endif
@if($result->criticalAlert)
<p><a href="{{ route('lab.critical.show', $result->criticalAlert) }}">عرض تنبيه حرج مرتبط</a></p>
@endif
</div>

@if(auth()->user()->hasPermission('results.approve'))
<div class="card">
<h3>إجراء الاعتماد</h3>
<form method="post" action="{{ route('lab.results.approve', $result) }}" style="margin-bottom:1rem">
@csrf
<label>ملاحظات المراجع (اختياري للاعتماد)</label>
<textarea name="comment" rows="3"></textarea>
<button class="btn" type="submit">اعتماد</button>
</form>
<form method="post" action="{{ route('lab.results.return', $result) }}" style="margin-bottom:1rem">
@csrf
<label>سبب الإعادة (مطلوب)</label>
<textarea name="comment" rows="2" required></textarea>
<button class="btn btn-secondary" type="submit">إعادة للتصحيح</button>
</form>
<form method="post" action="{{ route('lab.results.reject', $result) }}">
@csrf
<label>سبب الرفض (مطلوب)</label>
<textarea name="comment" rows="2" required></textarea>
<button class="btn btn-danger" type="submit">رفض</button>
</form>
<p class="muted">الاعتماد عبر صلاحية results.approve فقط (ليس اسم الدور).</p>
</div>
@else
<div class="card"><p class="muted">يمكنك عرض قائمة المراجعة، لكن الاعتماد يتطلب صلاحية results.approve.</p></div>
@endif

@if($result->reviews->isNotEmpty())
<div class="card"><h3>سجل المراجعة</h3>
<ul>@foreach($result->reviews as $review)
<li>{{ $review->reviewed_at }} — {{ $review->action }} — {{ $review->reviewer->name }}: {{ $review->comment }}</li>
@endforeach</ul></div>
@endif
@endsection
