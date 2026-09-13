@extends('layouts.app')
@section('heading', 'لوحة ذكاء الأعمال')
@section('subheading', 'بطاقات ومؤشرات أداء من طبقة BI فقط — لا تشخيص طبي')
@section('content')
<div class="card">
    <form method="get" class="grid-2">
        <div>
            <label>من تاريخ</label>
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
                    <option value="{{ $test->id }}" @selected($filters->testId === $test->id)>{{ $test->name_ar }} ({{ $test->code }})</option>
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
            <label>حالة الطلب</label>
            <select name="status">
                <option value="">الكل</option>
                @foreach($statuses as $code => $label)
                    <option value="{{ $code }}" @selected($filters->status === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions" style="grid-column: 1 / -1">
            <button class="btn" type="submit">تطبيق التصفية</button>
            <a class="btn btn-ghost" href="{{ route('admin.bi.index') }}">إعادة تعيين</a>
        </div>
    </form>
</div>

<div class="stats" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr))">
    <div class="stat"><span class="muted">المرضى</span><strong>{{ $cards['patients'] }}</strong></div>
    <div class="stat"><span class="muted">الأطباء</span><strong>{{ $cards['doctors'] }}</strong></div>
    <div class="stat"><span class="muted">دليل الفحوصات</span><strong>{{ $cards['tests_catalog'] }}</strong></div>
    <div class="stat"><span class="muted">طلبات اليوم</span><strong>{{ $cards['orders_today'] }}</strong></div>
    <div class="stat"><span class="muted">عينات بانتظار</span><strong>{{ $cards['samples_awaiting'] }}</strong></div>
    <div class="stat"><span class="muted">قيد التحليل</span><strong>{{ $cards['in_analysis'] }}</strong></div>
    <div class="stat"><span class="muted">بانتظار المراجعة</span><strong>{{ $cards['pending_review'] }}</strong></div>
    <div class="stat"><span class="muted">جاهز / مكتمل</span><strong>{{ $cards['ready_completed'] }}</strong></div>
    <div class="stat"><span class="muted">مؤجل</span><strong>{{ $cards['deferred'] }}</strong></div>
    <div class="stat"><span class="muted">مرفوض</span><strong>{{ $cards['rejected'] }}</strong></div>
    <div class="stat"><span class="muted">حالات حرجة</span><strong>{{ $cards['critical_cases'] }}</strong></div>
    <div class="stat"><span class="muted">حرج مفتوح</span><strong>{{ $cards['critical_open'] }}</strong></div>
</div>

<div class="card">
    <h3>مؤشرات الأداء (TAT)</h3>
    <p class="muted">مولَّد: {{ $generatedAt }} — الأرقام من طوابع زمنية حقيقية فقط.</p>
    <table>
        <thead>
            <tr>
                <th>المؤشر</th>
                <th>القيمة</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>طلبات في الفترة</td><td>{{ $performance['orders_in_period'] }}</td></tr>
            <tr><td>فحوصات (بنود) في الفترة</td><td>{{ $performance['tests_in_period'] }}</td></tr>
            <tr><td>حالات حرجة في الفترة</td><td>{{ $performance['critical_in_period'] }}</td></tr>
            <tr><td>متوسط زمن إنجاز الطلب</td><td>{{ $performance['avg_order_tat_hours'] ?? 'غير متاح' }}</td></tr>
            <tr><td>متوسط زمن معالجة الحرج</td><td>{{ $performance['avg_critical_handling_hours'] ?? 'غير متاح' }}</td></tr>
        </tbody>
    </table>
    @if(!empty($performance['limitations']))
        <p class="muted" style="margin-top:1rem">قيود الحساب (طوابع مفقودة — لم تُختلق قيم):</p>
        <ul class="muted">
            @foreach(array_slice($performance['limitations'], 0, 5) as $limitation)
                <li>{{ $limitation }}</li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
