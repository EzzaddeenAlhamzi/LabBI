@extends('layouts.admin')
@section('heading', 'لوحة الطبيب')
@section('subheading', 'نظرة سريرية على طلباتك ومرضاك ونتائجك')
@section('page_actions')
    <a class="btn" href="{{ route('doctor.orders.create') }}"><x-icon name="plus" /> إنشاء طلب فحص</a>
    <a class="btn btn-secondary" href="{{ route('doctor.results.index') }}"><x-icon name="flask-conical" /> النتائج</a>
@endsection
@section('content')
@php
$kpis = [
    ['key' => 'ordersCount', 'label' => 'طلباتي', 'icon' => 'clipboard', 'tone' => 'blue', 'href' => route('doctor.orders.index'), 'link' => 'كل الطلبات'],
    ['key' => 'patientsCount', 'label' => 'مرضاي', 'icon' => 'patients', 'tone' => 'teal', 'href' => route('doctor.patients.index'), 'link' => 'المرضى'],
    ['key' => 'resultsCount', 'label' => 'نتائج معتمدة', 'icon' => 'check-circle', 'tone' => 'green', 'href' => route('doctor.results.index'), 'link' => 'النتائج'],
    ['key' => 'criticalCount', 'label' => 'تنبيهات حرجة مفتوحة', 'icon' => 'alert-octagon', 'tone' => 'red', 'href' => route('doctor.critical.index'), 'link' => 'الحرجة'],
    ['key' => 'ordersToday', 'label' => 'طلبات اليوم', 'icon' => 'calendar', 'tone' => 'purple', 'href' => route('doctor.orders.index'), 'link' => 'طلبات اليوم'],
];
$statusColors = [
    'requested' => '#64748B', 'sample_received' => '#0EA5E9', 'in_analysis' => '#2563EB',
    'pending_review' => '#D97706', 'ready' => '#0D9488', 'completed' => '#16A34A',
    'archived' => '#94A3B8', 'deferred' => '#B45309', 'rejected' => '#DC2626',
];
@endphp

<div class="kpi-grid">
    @foreach($kpis as $kpi)
    <div class="kpi">
        <div class="kpi-top">
            <span class="kpi-label">{{ $kpi['label'] }}</span>
            <span class="kpi-icon tone-{{ $kpi['tone'] }}"><x-icon :name="$kpi['icon']" /></span>
        </div>
        <div class="kpi-value">{{ number_format(${$kpi['key']} ?? 0) }}</div>
        <div class="kpi-foot"><span class="kpi-note">{{ now()->format('Y-m-d') }}</span><a class="small" href="{{ $kpi['href'] }}">{{ $kpi['link'] }} ←</a></div>
    </div>
    @endforeach
</div>

<div class="grid-3" style="grid-template-columns: 2fr 1fr; align-items:start">
    <div class="card">
        <div class="card-title"><x-icon name="activity" /> طلباتي — آخر 14 يومًا</div>
        <p class="card-sub">عدد طلبات الفحص التي أنشأتها لكل يوم</p>
        <div id="ordersChart" style="height:300px"></div>
    </div>
    <div class="card">
        <div class="card-title"><x-icon name="chart-pie" /> حالات طلباتي</div>
        <p class="card-sub">حالات طلباتك خلال آخر 30 يومًا</p>
        <div id="statusDonut"></div>
    </div>
</div>

<div class="card">
    <div class="card-title"><x-icon name="activity" /> إجراءات سريعة</div>
    <p class="card-sub">اختصارات مساحة العمل السريرية</p>
    <div class="flex" style="flex-wrap:wrap">
        <a class="btn" href="{{ route('doctor.orders.create') }}"><x-icon name="plus" /> طلب فحص جديد</a>
        <a class="btn btn-secondary" href="{{ route('doctor.patients.index') }}"><x-icon name="patients" /> مرضاي</a>
        <a class="btn btn-secondary" href="{{ route('doctor.results.index') }}"><x-icon name="flask-conical" /> النتائج المعتمدة</a>
        <a class="btn btn-ghost" href="{{ route('doctor.critical.index') }}"><x-icon name="alert-octagon" /> النتائج الحرجة</a>
        <a class="btn btn-ghost" href="{{ route('doctor.reports.index') }}"><x-icon name="file-text" /> التقارير PDF</a>
        <a class="btn btn-ghost" href="{{ route('doctor.comparison.index') }}"><x-icon name="chart" /> المقارنة التاريخية</a>
        <a class="btn btn-ghost" href="{{ route('doctor.requests.index') }}"><x-icon name="mail" /> طلبات المرضى</a>
    </div>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الطلب</th><th>المريض</th><th>الحالة</th><th>تاريخ الطلب</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($recentOrders as $order)
    <tr>
        <td dir="ltr"><b>{{ $order->order_number }}</b></td>
        <td>{{ $order->patient->full_name }}</td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td class="muted-cell" dir="ltr">{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('doctor.orders.show', $order) }}"><x-icon name="eye" /> فتح</a></div></td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><div class="empty-icon"><x-icon name="clipboard" /></div><h3>لا طلبات بعد</h3><p>لم تنشئ أي طلبات فحص بعد. ابدأ بإنشاء طلب جديد.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.LabBICharts) return;
    var statusColors = @json($statusColors);
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
});
</script>
@endpush
