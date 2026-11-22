@extends('layouts.admin')
@section('title', 'لوحة ذكاء الأعمال')
@section('heading', 'لوحة ذكاء الأعمال')
@section('subheading', 'بطاقات ومؤشرات أداء من طبقة BI فقط — لا تشخيص طبي')
@section('content')
<div class="card">
    <form method="get" class="form-grid" style="grid-template-columns: repeat(3, 1fr); align-items:end">
        <div class="field" style="margin-bottom:0">
            <label for="date_from">من تاريخ</label>
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
                    <option value="{{ $test->id }}" @selected($filters->testId === $test->id)>{{ $test->name_ar }} ({{ $test->code }})</option>
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
            <label for="status">حالة الطلب</label>
            <select id="status" name="status">
                <option value="">الكل</option>
                @foreach($statuses as $code => $label)
                    <option value="{{ $code }}" @selected($filters->status === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="margin-bottom:0;grid-column:1/-1">
            <div class="flex" style="gap:.5rem">
                <button class="btn" type="submit"><x-icon name="filter" /> تطبيق التصفية</button>
                <a class="btn btn-ghost" href="{{ route('admin.bi.index') }}">إعادة تعيين</a>
            </div>
        </div>
    </form>
</div>

@php
$kpiDefs = [
    ['patients', 'المرضى', 'patients', 'teal'],
    ['doctors', 'الأطباء', 'doctor', 'blue'],
    ['tests_catalog', 'دليل الفحوصات', 'test-tubes', 'cyan'],
    ['orders_today', 'طلبات اليوم', 'clipboard', 'purple'],
    ['samples_awaiting', 'عينات بانتظار', 'test-tube', 'amber'],
    ['in_analysis', 'قيد التحليل', 'activity', 'blue'],
    ['pending_review', 'بانتظار المراجعة', 'clock', 'amber'],
    ['ready_completed', 'جاهز / مكتمل', 'check-circle', 'green'],
    ['deferred', 'مؤجل', 'clock', 'slate'],
    ['rejected', 'مرفوض', 'x', 'red'],
    ['critical_cases', 'حالات حرجة', 'alert-octagon', 'red'],
    ['critical_open', 'حرج مفتوح', 'alert', 'red'],
];
$statusColors = [
    'requested' => '#64748B', 'sample_received' => '#0EA5E9', 'in_analysis' => '#2563EB',
    'pending_review' => '#D97706', 'ready' => '#0D9488', 'completed' => '#16A34A',
    'archived' => '#94A3B8', 'deferred' => '#B45309', 'rejected' => '#DC2626',
];
@endphp

<div class="kpi-grid">
    @foreach($kpiDefs as [$key, $label, $icon, $tone])
    <div class="kpi">
        <div class="kpi-top">
            <span class="kpi-label">{{ $label }}</span>
            <span class="kpi-icon tone-{{ $tone }}"><x-icon :name="$icon" /></span>
        </div>
        <div class="kpi-value">{{ number_format($cards[$key] ?? 0) }}</div>
    </div>
    @endforeach
</div>

<div class="grid-3" style="grid-template-columns: 2fr 1fr; align-items:start">
    <div class="card">
        <div class="card-title"><x-icon name="activity" /> الطلبات — آخر 14 يومًا (حسب التصفية)</div>
        <p class="card-sub">عدد طلبات الفحص اليومية ضمن الفلاتر المحددة</p>
        <div id="ordersChart" style="height:300px"></div>
    </div>
    <div class="card">
        <div class="card-title"><x-icon name="chart-pie" /> توزيع حالات الطلبات</div>
        <p class="card-sub">ضمن الفترة والفلاتر المحددة</p>
        <div id="statusDonut"></div>
    </div>
</div>

<div class="grid-2" style="align-items:start">
    <div class="card">
        <div class="card-title"><x-icon name="building" /> عبء العمل حسب القسم</div>
        <p class="card-sub">عدد بنود الفحوصات لكل قسم مختبري</p>
        <div id="deptBars"></div>
    </div>
    <div class="card">
        <div class="card-title"><x-icon name="timer" /> مؤشرات الأداء (TAT)</div>
        <p class="card-sub">مولَّد: {{ $generatedAt }} — الأرقام من طوابع زمنية حقيقية فقط.</p>
        <table class="data">
            <tbody>
                <tr><td>طلبات في الفترة</td><td class="num"><b>{{ $performance['orders_in_period'] }}</b></td></tr>
                <tr><td>فحوصات (بنود) في الفترة</td><td class="num"><b>{{ $performance['tests_in_period'] }}</b></td></tr>
                <tr><td>حالات حرجة في الفترة</td><td class="num"><b>{{ $performance['critical_in_period'] }}</b></td></tr>
                <tr><td>متوسط زمن إنجاز الطلب</td><td class="num"><b>{{ $performance['avg_order_tat_hours'] ?? 'غير متاح' }}</b></td></tr>
                <tr><td>متوسط زمن معالجة الحرج</td><td class="num"><b>{{ $performance['avg_critical_handling_hours'] ?? 'غير متاح' }}</b></td></tr>
            </tbody>
        </table>
        @if(!empty($performance['limitations']))
            <p class="muted small mt-2 mb-0">قيود الحساب (طوابع مفقودة — لم تُختلق قيم):</p>
            <ul class="muted small" style="padding-inline-start:1.1rem">
                @foreach(array_slice($performance['limitations'], 0, 5) as $limitation)
                    <li>{{ $limitation }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.LabBICharts) return;
    var ordersEl = document.getElementById('ordersChart');
    if (ordersEl) {
        LabBICharts.area(ordersEl, {
            labels: @json($orderSeries['labels']),
            series: [{ name: 'الطلبات', data: @json($orderSeries['counts']), color: '#0D9488' }],
            height: 300
        });
    }
    var donutEl = document.getElementById('statusDonut');
    if (donutEl) {
        LabBICharts.donut(donutEl, {
            data: @json(collect($statusBreakdown)->map(fn($s) => ['label' => $s['label'], 'value' => $s['count'], 'color' => $statusColors[$s['status']] ?? '#64748B'])->values()),
            centerLabel: 'إجمالي الطلبات',
            centerValue: @json(collect($statusBreakdown)->sum('count'))
        });
    }
    var deptEl = document.getElementById('deptBars');
    if (deptEl) {
        LabBICharts.bars(deptEl, {
            data: @json(collect($departmentWorkload)->map(fn($d) => ['label' => $d['label'], 'value' => $d['count']])->values())
        });
    }
});
</script>
@endpush
