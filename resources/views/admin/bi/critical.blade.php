@extends('layouts.admin')
@section('title', 'لوحة الحالات الحرجة')
@section('heading', 'لوحة الحالات الحرجة')
@section('subheading', 'عرض التنبيهات المخزّنة من المرحلة 2 فقط — دون إعادة تشغيل محرك القيم الحرجة')
@section('content')
<div class="card">
    <form method="get" class="form-grid" style="grid-template-columns: repeat(3, 1fr); align-items:end">
        <div class="field" style="margin-bottom:0">
            <label for="date_from">من تاريخ الاكتشاف</label>
            <input type="date" id="date_from" name="date_from" value="{{ $filters->dateFrom?->toDateString() }}">
        </div>
        <div class="field" style="margin-bottom:0">
            <label for="date_to">إلى تاريخ</label>
            <input type="date" id="date_to" name="date_to" value="{{ $filters->dateTo?->toDateString() }}">
        </div>
        <div class="field" style="margin-bottom:0">
            <label for="test_id">الفحص</label>
            <select id="test_id" name="test_id">
                <option value="">الكل</option>
                @foreach($tests as $test)
                    <option value="{{ $test->id }}" @selected($filters->testId === $test->id)>{{ $test->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin-bottom:0">
            <label for="doctor_id">الطبيب</label>
            <select id="doctor_id" name="doctor_id">
                <option value="">الكل</option>
                @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}" @selected($filters->doctorId === $doctor->id)>{{ $doctor->user?->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin-bottom:0">
            <label for="department_id">القسم</label>
            <select id="department_id" name="department_id">
                <option value="">الكل</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected($filters->departmentId === $dept->id)>{{ $dept->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin-bottom:0">
            <label for="status">الحالة</label>
            <select id="status" name="status">
                <option value="">الكل</option>
                @foreach($statuses as $code => $label)
                    <option value="{{ $code }}" @selected($filters->status === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin-bottom:0;grid-column:1/-1">
            <button class="btn" type="submit"><x-icon name="filter" /> تطبيق التصفية</button>
        </div>
    </form>
</div>

<div class="kpi-grid">
    <div class="kpi"><div class="kpi-top"><span class="kpi-label">الإجمالي</span><span class="kpi-icon tone-navy"><x-icon name="alert-octagon" /></span></div><div class="kpi-value">{{ number_format($summary['total']) }}</div></div>
    <div class="kpi"><div class="kpi-top"><span class="kpi-label">مفتوح</span><span class="kpi-icon tone-red"><x-icon name="alert" /></span></div><div class="kpi-value">{{ number_format($summary['open']) }}</div></div>
    <div class="kpi"><div class="kpi-top"><span class="kpi-label">مُقرّ</span><span class="kpi-icon tone-amber"><x-icon name="check" /></span></div><div class="kpi-value">{{ number_format($summary['acknowledged']) }}</div></div>
    <div class="kpi"><div class="kpi-top"><span class="kpi-label">مغلق</span><span class="kpi-icon tone-green"><x-icon name="check-circle" /></span></div><div class="kpi-value">{{ number_format($summary['closed']) }}</div></div>
    <div class="kpi"><div class="kpi-top"><span class="kpi-label">متوسط المعالجة (ساعة)</span><span class="kpi-icon tone-blue"><x-icon name="timer" /></span></div><div class="kpi-value">{{ $summary['avg_handling_hours'] ?? '—' }}</div></div>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>الاكتشاف</th>
                <th>المريض</th>
                <th>الفحص</th>
                <th>القيمة</th>
                <th>الطبيب</th>
                <th>القسم</th>
                <th>الحالة</th>
                <th>زمن المعالجة</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @php($alert = $row['alert'])
                <tr>
                    <td class="muted-cell" dir="ltr" style="text-align:start">{{ $alert->discovered_at?->format('Y-m-d H:i') }}</td>
                    <td><b>{{ $alert->patient?->full_name }}</b></td>
                    <td>{{ $alert->test?->name_ar }}</td>
                    <td><b style="color:var(--danger)">{{ $alert->detected_value }}</b> <span class="muted small">{{ $alert->unit }}</span></td>
                    <td>{{ $alert->doctor?->user?->name }}</td>
                    <td>{{ $alert->test?->department?->name_ar ?? '—' }}</td>
                    <td><x-status-badge :status="$alert->status" :label="$statuses[$alert->status] ?? $alert->status" /></td>
                    <td class="muted-cell">{{ $row['handling_hours'] ?? 'غير متاح' }}</td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty"><div class="empty-icon"><x-icon name="alert-octagon" /></div><h3>لا توجد حالات حرجة</h3><p>لا توجد تنبيهات مطابقة لمعايير البحث.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">{{ $rows->links('admin.partials.pagination') }}</div>
</div>
@endsection
