@extends('layouts.admin')
@section('title', 'المديات المرجعية والحرجة')
@section('heading', 'المديات المرجعية والحرجة')
@section('subheading', 'بيانات المديات فقط — محرك القيم الحرجة غير مضمّن في المرحلة 1')
@section('content')
<div class="grid-2" style="align-items:start">
<div class="card flush">
    <div class="card-title" style="padding:1rem 1rem 0"><x-icon name="ruler" /> المديات المسجلة ({{ $ranges->total() }})</div>
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الفحص</th><th>الوحدة</th><th>المدى الطبيعي</th><th>المدى الحرج</th><th>الجنس / العمر</th><th>الحالة</th></tr></thead>
    <tbody>
    @forelse($ranges as $range)
    <tr>
        <td><b>{{ $range->test->code }}</b> — {{ $range->test->name_ar }}</td>
        <td><span class="badge badge-neutral">{{ $range->test->unit ?: '—' }}</span></td>
        <td class="num" dir="ltr" style="text-align:end">{{ $range->normal_low ?? '—' }} – {{ $range->normal_high ?? '—' }}</td>
        <td class="num" dir="ltr" style="text-align:end; color: var(--danger); font-weight:600">{{ $range->critical_low ?? '—' }} – {{ $range->critical_high ?? '—' }}</td>
        <td class="muted-cell">{{ $range->sex ?: 'الكل' }}{{ ($range->age_min || $range->age_max) ? ' / '.($range->age_min ?? '0').'–'.($range->age_max ?? '∞') : '' }}</td>
        <td><x-status-badge :status="$range->is_active ? 'active' : 'inactive'" :label="$range->is_active ? 'نشط' : 'موقوف'" /></td>
    </tr>
    @empty
    <tr><td colspan="6"><div class="empty"><div class="empty-icon"><x-icon name="ruler" /></div><h3>لا توجد مديات</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($ranges->hasPages())<div class="pager">{{ $ranges->links('admin.partials.pagination') }}</div>@endif
</div>
<div class="card">
    <div class="form-section-title"><x-icon name="plus" /> إضافة مدى مرجعي</div>
    <form method="post" action="{{ route('admin.ranges.store') }}">@csrf
        <div class="field">
            <label for="test_id">الفحص <span class="req">*</span></label>
            <select id="test_id" name="test_id" required>
                @foreach($tests as $test)<option value="{{ $test->id }}">{{ $test->code }} — {{ $test->name_ar }}</option>@endforeach
            </select>
        </div>
        <div class="form-grid">
            <div class="field"><label for="normal_low">طبيعي — من</label><input id="normal_low" name="normal_low" type="number" step="any" dir="ltr" style="text-align:start"></div>
            <div class="field"><label for="normal_high">طبيعي — إلى</label><input id="normal_high" name="normal_high" type="number" step="any" dir="ltr" style="text-align:start"></div>
            <div class="field"><label for="critical_low">حرج — من</label><input id="critical_low" name="critical_low" type="number" step="any" dir="ltr" style="text-align:start"></div>
            <div class="field"><label for="critical_high">حرج — إلى</label><input id="critical_high" name="critical_high" type="number" step="any" dir="ltr" style="text-align:start"></div>
        </div>
        <button class="btn" type="submit"><x-icon name="check" /> حفظ المدى</button>
    </form>
</div>
</div>
@endsection
