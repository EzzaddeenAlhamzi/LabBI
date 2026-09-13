@extends('layouts.app')
@section('heading', 'لوحة الحالات الحرجة')
@section('subheading', 'عرض التنبيهات المخزّنة من المرحلة 2 فقط — دون إعادة تشغيل محرك القيم الحرجة')
@section('content')
<div class="card">
    <form method="get" class="grid-2">
        <div>
            <label>من تاريخ الاكتشاف</label>
            <input type="date" name="date_from" value="{{ $filters->dateFrom?->toDateString() }}">
        </div>
        <div>
            <label>إلى تاريخ</label>
            <input type="date" name="date_to" value="{{ $filters->dateTo?->toDateString() }}">
        </div>
        <div>
            <label>الفحص</label>
            <select name="test_id">
                <option value="">الكل</option>
                @foreach($tests as $test)
                    <option value="{{ $test->id }}" @selected($filters->testId === $test->id)>{{ $test->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>الطبيب</label>
            <select name="doctor_id">
                <option value="">الكل</option>
                @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}" @selected($filters->doctorId === $doctor->id)>{{ $doctor->user?->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>القسم</label>
            <select name="department_id">
                <option value="">الكل</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected($filters->departmentId === $dept->id)>{{ $dept->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>الحالة</label>
            <select name="status">
                <option value="">الكل</option>
                @foreach($statuses as $code => $label)
                    <option value="{{ $code }}" @selected($filters->status === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions" style="grid-column: 1 / -1">
            <button class="btn" type="submit">تصفية</button>
        </div>
    </form>
</div>

<div class="stats" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr))">
    <div class="stat"><span class="muted">الإجمالي</span><strong>{{ $summary['total'] }}</strong></div>
    <div class="stat"><span class="muted">مفتوح</span><strong>{{ $summary['open'] }}</strong></div>
    <div class="stat"><span class="muted">مُقرّ</span><strong>{{ $summary['acknowledged'] }}</strong></div>
    <div class="stat"><span class="muted">مغلق</span><strong>{{ $summary['closed'] }}</strong></div>
    <div class="stat"><span class="muted">متوسط المعالجة</span><strong>{{ $summary['avg_handling_hours'] ?? '—' }}</strong></div>
</div>

<div class="card">
    <table>
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
                    <td>{{ $alert->discovered_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $alert->patient?->full_name }}</td>
                    <td>{{ $alert->test?->name_ar }}</td>
                    <td>{{ $alert->detected_value }} {{ $alert->unit }}</td>
                    <td>{{ $alert->doctor?->user?->name }}</td>
                    <td>{{ $alert->test?->department?->name_ar ?? '—' }}</td>
                    <td><span class="badge">{{ $statuses[$alert->status] ?? $alert->status }}</span></td>
                    <td>{{ $row['handling_hours'] ?? 'غير متاح' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">لا توجد حالات حرجة مطابقة.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:1rem">{{ $rows->links() }}</div>
</div>
@endsection
