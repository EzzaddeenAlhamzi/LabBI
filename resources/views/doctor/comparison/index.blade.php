@extends('layouts.admin')
@section('heading', 'المقارنة التاريخية')
@section('subheading', 'نتائج معتمدة فقط — اتجاه تشغيلي غير تشخيصي')
@section('content')
<div class="card">
    <div class="card-title"><x-icon name="chart" /> معايير المقارنة</div>
    <p class="card-sub">اختر المريض والفحص والفترة لعرض النتائج المعتمدة واتجاهها.</p>
    <form method="get" class="form-grid" style="grid-template-columns:repeat(4,1fr);align-items:end">
        <div class="field">
            <label for="patient_id">المريض <span class="req">*</span></label>
            <select id="patient_id" name="patient_id" required>
                <option value="">اختر المريض</option>
                @foreach($patients as $patient)
                    <option value="{{ $patient->id }}" @selected($filters->patientId === $patient->id)>
                        {{ $patient->full_name }} — {{ $patient->medical_file_number }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="test_id">الفحص</label>
            <select id="test_id" name="test_id">
                <option value="">اختر الفحص</option>
                @foreach($tests as $test)
                    <option value="{{ $test->id }}" @selected($filters->testId === $test->id)>{{ $test->name_ar }} ({{ $test->code }})</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="date_from">من تاريخ الاعتماد</label>
            <input type="date" id="date_from" name="date_from" value="{{ $filters->dateFrom?->toDateString() }}">
        </div>
        <div class="field">
            <label for="date_to">إلى تاريخ</label>
            <input type="date" id="date_to" name="date_to" value="{{ $filters->dateTo?->toDateString() }}">
        </div>
        <div class="form-actions" style="grid-column:1/-1">
            <button class="btn" type="submit"><x-icon name="search" /> مقارنة</button>
        </div>
    </form>
</div>

@if($error)
    <div class="alert alert-error"><x-icon name="alert" /><span>{{ $error }}</span></div>
@endif

@if($result)
    <div class="card">
        <div class="card-title"><x-icon name="activity" /> الاتجاه (غير طبي)</div>
        <p>
            @php $trendTones = ['rising'=>'warning','falling'=>'info','stable'=>'success','incompatible_units'=>'danger','insufficient_data'=>'neutral']; @endphp
            <x-status-badge :tone="$trendTones[$result['trend']['trend'] ?? 'neutral'] ?? 'neutral'" :label="$result['trend']['label_ar']" />
            @if($result['trend']['relative_change'] !== null)
                <span class="muted-cell small">التغير النسبي: {{ number_format($result['trend']['relative_change'] * 100, 2) }}%</span>
            @endif
        </p>
        <p class="muted small">هذا المؤشر تشغيلي فقط ولا يمثّل تشخيصاً أو نصيحة علاجية.</p>
        @if(!empty($result['limitations']))
            <ul class="muted small" style="margin:.5rem 0 0;padding-inline-start:1.2rem">
                @foreach($result['limitations'] as $limitation)
                    <li>{{ $limitation }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card flush">
        <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>القيمة</th>
                    <th>الوحدة</th>
                    <th>التصنيف</th>
                    <th>المدى المرجعي</th>
                    <th>حالة الحرج</th>
                </tr>
            </thead>
            <tbody>
                @forelse($result['rows'] as $row)
                    <tr>
                        <td dir="ltr">{{ $row['date'] }}</td>
                        <td dir="ltr"><b>{{ $row['value'] }}</b></td>
                        <td dir="ltr">{{ $row['unit'] }}</td>
                        <td>
                            @php
                                $ctone = match($row['classification'] ?? '') {
                                    'normal' => 'success',
                                    'critical','panic','critical_low','critical_high' => 'danger',
                                    'borderline','high','low' => 'warning',
                                    default => 'neutral',
                                };
                            @endphp
                            <x-status-badge :tone="$ctone" :label="$row['classification_label']" />
                        </td>
                        <td class="muted-cell" dir="ltr">{{ $row['reference_range'] ?? '—' }}</td>
                        <td class="muted-cell">{{ $row['critical_status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><div class="empty-icon"><x-icon name="chart" /></div><h3>لا نتائج معتمدة</h3><p>لا توجد نتائج معتمدة في هذه الفترة.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
@endif
@endsection
