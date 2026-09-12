@extends('layouts.app')
@section('heading', 'المقارنة التاريخية')
@section('subheading', 'نتائج معتمدة فقط — اتجاه تشغيلي غير تشخيصي')
@section('content')
<div class="card">
    <form method="get" class="grid-2">
        <div>
            <label>المريض</label>
            <select name="patient_id" required>
                <option value="">اختر المريض</option>
                @foreach($patients as $patient)
                    <option value="{{ $patient->id }}" @selected($filters->patientId === $patient->id)>
                        {{ $patient->full_name }} — {{ $patient->medical_file_number }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label>الفحص</label>
            <select name="test_id">
                <option value="">اختر الفحص</option>
                @foreach($tests as $test)
                    <option value="{{ $test->id }}" @selected($filters->testId === $test->id)>{{ $test->name_ar }} ({{ $test->code }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>من تاريخ الاعتماد</label>
            <input type="date" name="date_from" value="{{ $filters->dateFrom?->toDateString() }}">
        </div>
        <div>
            <label>إلى تاريخ</label>
            <input type="date" name="date_to" value="{{ $filters->dateTo?->toDateString() }}">
        </div>
        <div class="actions" style="grid-column: 1 / -1">
            <button class="btn" type="submit">مقارنة</button>
        </div>
    </form>
</div>

@if($error)
    <div class="errors">{{ $error }}</div>
@endif

@if($result)
    <div class="card">
        <h3>الاتجاه (غير طبي)</h3>
        <p>
            <span class="badge" style="background: {{ $result['trend']['color'] }}; color: #fff">
                {{ $result['trend']['label_ar'] }}
            </span>
            @if($result['trend']['relative_change'] !== null)
                <span class="muted">التغير النسبي: {{ number_format($result['trend']['relative_change'] * 100, 2) }}%</span>
            @endif
        </p>
        <p class="muted">هذا المؤشر تشغيلي فقط ولا يمثّل تشخيصاً أو نصيحة علاجية.</p>
        @if(!empty($result['limitations']))
            <ul class="muted">
                @foreach($result['limitations'] as $limitation)
                    <li>{{ $limitation }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card">
        <h3>النتائج المعتمدة — {{ $result['patient']->full_name }}</h3>
        <table>
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
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['value'] }}</td>
                        <td>{{ $row['unit'] }}</td>
                        <td><span class="badge">{{ $row['classification_label'] }}</span></td>
                        <td>{{ $row['reference_range'] ?? '—' }}</td>
                        <td>{{ $row['critical_status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">لا توجد نتائج معتمدة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
