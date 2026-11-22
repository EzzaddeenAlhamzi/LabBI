@extends('layouts.admin')
@section('title', 'تفاصيل الفحص')
@section('heading', $test->name_ar)
@section('subheading', $test->code)
@section('page_actions')
    <a class="btn btn-secondary" href="{{ route('admin.tests.index') }}"><x-icon name="chevron-right" /> رجوع</a>
    <a class="btn" href="{{ route('admin.ranges.index') }}"><x-icon name="ruler" /> إدارة المديات</a>
@endsection
@section('content')
<div class="card">
    <div class="card-title"><x-icon name="test-tubes" /> بيانات الفحص</div>
    <div class="dl">
        <div class="item"><div class="k">الرمز</div><div class="v"><code>{{ $test->code }}</code></div></div>
        <div class="item"><div class="k">الاسم بالعربية</div><div class="v">{{ $test->name_ar }}</div></div>
        <div class="item"><div class="k">الاسم بالإنجليزية</div><div class="v" dir="ltr" style="text-align:start">{{ $test->name_en ?: '—' }}</div></div>
        <div class="item"><div class="k">الوحدة</div><div class="v"><span class="badge badge-neutral">{{ $test->unit ?: '—' }}</span></div></div>
        <div class="item"><div class="k">القسم</div><div class="v">{{ $test->department?->name_ar ?: '—' }}</div></div>
        <div class="item"><div class="k">الحالة</div><div class="v"><x-status-badge :status="$test->is_active ? 'active' : 'inactive'" :label="$test->is_active ? 'نشط' : 'موقوف'" /></div></div>
    </div>
</div>
<div class="card flush">
    <div class="card-title" style="padding:1rem 1rem 0"><x-icon name="ruler" /> المديات المرجعية والحرجة ({{ $test->referenceRanges->count() }})</div>
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>طبيعي من</th><th>طبيعي إلى</th><th>حرج من</th><th>حرج إلى</th><th>الجنس</th><th>العمر</th><th>فعّال من</th><th>الحالة</th></tr></thead>
    <tbody>
    @forelse($test->referenceRanges as $range)
    <tr>
        <td class="num">{{ $range->normal_low ?? '—' }}</td>
        <td class="num">{{ $range->normal_high ?? '—' }}</td>
        <td class="num">{{ $range->critical_low ?? '—' }}</td>
        <td class="num">{{ $range->critical_high ?? '—' }}</td>
        <td>{{ $range->sex ?: '—' }}</td>
        <td class="muted-cell">{{ $range->age_min || $range->age_max ? trim(($range->age_min ?? '0').' - '.($range->age_max ?? '∞')) : '—' }}</td>
        <td class="muted-cell">{{ optional($range->effective_from)->format('Y-m-d') ?: '—' }}</td>
        <td><x-status-badge :status="$range->is_active ? 'active' : 'inactive'" :label="$range->is_active ? 'نشط' : 'موقوف'" /></td>
    </tr>
    @empty
    <tr><td colspan="8"><div class="empty"><h3>لا توجد مديات</h3><p>لم يتم تعريف مديات مرجعية لهذا الفحص — أضفها من شاشة المديات المرجعية.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
@endsection
