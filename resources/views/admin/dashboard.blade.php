@extends('layouts.admin')
@section('title', 'لوحة التحكم')
@section('heading', 'لوحة تحكم الإدارة')
@section('subheading', 'مؤشرات تشغيلية شاملة على المختبر — جميع الأرقام من قاعدة البيانات الفعلية')
@section('page_actions')
    <a class="btn btn-secondary" href="{{ route('admin.bi.index') }}"><x-icon name="chart" /> ذكاء الأعمال</a>
    <a class="btn btn-secondary" href="{{ route('admin.reports.operational') }}"><x-icon name="file-text" /> التقارير التشغيلية</a>
    <a class="btn" href="{{ route('admin.orders.create') }}"><x-icon name="plus" /> طلب جديد</a>
@endsection
@section('content')
@php
$statusColors = [
    'requested' => '#64748B', 'sample_received' => '#0EA5E9', 'in_analysis' => '#2563EB',
    'pending_review' => '#D97706', 'ready' => '#0D9488', 'completed' => '#16A34A',
    'archived' => '#94A3B8', 'deferred' => '#B45309', 'rejected' => '#DC2626',
];
$kpis = [
    ['key' => 'patients', 'label' => 'إجمالي المرضى', 'icon' => 'patients', 'tone' => 'teal'],
    ['key' => 'doctors', 'label' => 'الأطباء', 'icon' => 'doctor', 'tone' => 'blue'],
    ['key' => 'orders_today', 'label' => 'طلبات اليوم', 'icon' => 'clipboard', 'tone' => 'purple'],
    ['key' => 'tests_catalog', 'label' => 'الفحوصات في الدليل', 'icon' => 'test-tubes', 'tone' => 'cyan'],
    ['key' => 'samples_awaiting', 'label' => 'عينات بانتظار الاستلام', 'icon' => 'test-tube', 'tone' => 'amber'],
    ['key' => 'in_analysis', 'label' => 'قيد التحليل', 'icon' => 'activity', 'tone' => 'blue'],
    ['key' => 'pending_review', 'label' => 'بانتظار المراجعة', 'icon' => 'clock', 'tone' => 'amber'],
    ['key' => 'ready_completed', 'label' => 'جاهز / مكتمل', 'icon' => 'check-circle', 'tone' => 'green'],
    ['key' => 'deferred', 'label' => 'مؤجل', 'icon' => 'clock', 'tone' => 'slate'],
    ['key' => 'rejected', 'label' => 'مرفوض', 'icon' => 'x', 'tone' => 'red'],
    ['key' => 'critical_cases', 'label' => 'الحالات الحرجة', 'icon' => 'alert-octagon', 'tone' => 'red'],
    ['key' => 'critical_open', 'label' => 'حرج مفتوح', 'icon' => 'alert', 'tone' => 'red'],
];
@endphp

<div class="kpi-grid">
    @foreach($kpis as $kpi)
    <div class="kpi">
        <div class="kpi-top">
            <span class="kpi-label">{{ $kpi['label'] }}</span>
            <span class="kpi-icon tone-{{ $kpi['tone'] }}"><x-icon :name="$kpi['icon']" /></span>
        </div>
        <div class="kpi-value">{{ number_format($cards[$kpi['key']] ?? 0) }}</div>
    </div>
    @endforeach
</div>

<div class="grid-3" style="grid-template-columns: 2fr 1fr; align-items:start">
    <div class="card">
        <div class="card-title"><x-icon name="activity" /> أداء الطلبات — آخر 14 يومًا</div>
        <p class="card-sub">عدد طلبات الفحص اليومية حسب تاريخ الطلب الفعلي</p>
        <div id="ordersChart" style="height:300px"></div>
    </div>
    <div class="card">
        <div class="card-title"><x-icon name="chart-pie" /> توزيع حالات الطلبات</div>
        <p class="card-sub">جميع الطلبات الحالية حسب حالتها</p>
        <div id="statusDonut"></div>
    </div>
</div>

<div class="grid-2" style="align-items:start">
    <div class="card">
        <div class="card-title"><x-icon name="building" /> عبء العمل حسب القسم</div>
        <p class="card-sub">عدد بنود الفحوصات المطلوبة لكل قسم مختبري</p>
        <div id="deptBars"></div>
    </div>
    <div class="card">
        <div class="card-title"><x-icon name="timer" /> مؤشرات الأداء</div>
        <p class="card-sub">محسوبة من الطوابع الزمنية الفعلية فقط</p>
        <div class="dl">
            <div class="item"><div class="k">طلبات في الفترة</div><div class="v">{{ $performance['orders_in_period'] ?? '—' }}</div></div>
            <div class="item"><div class="k">فحوصات في الفترة</div><div class="v">{{ $performance['tests_in_period'] ?? '—' }}</div></div>
            <div class="item"><div class="k">حالات حرجة في الفترة</div><div class="v">{{ $performance['critical_in_period'] ?? '—' }}</div></div>
            <div class="item"><div class="k">متوسط زمن إنجاز الطلب</div><div class="v">{{ $performance['avg_order_tat_hours'] ?? 'غير متاح' }}</div></div>
            <div class="item"><div class="k">متوسط معالجة الحرج</div><div class="v">{{ $performance['avg_critical_handling_hours'] ?? 'غير متاح' }}</div></div>
        </div>
        <div class="mt-2 flex-between">
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.critical-cases.index') }}">لوحة الحالات الحرجة</a>
            <a class="btn btn-secondary btn-sm" href="{{ route('admin.reports.operational') }}">التقرير التشغيلي الكامل</a>
        </div>
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
