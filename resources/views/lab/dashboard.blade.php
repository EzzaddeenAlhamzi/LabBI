@extends('layouts.admin')
@section('heading', 'لوحة المختبر')
@section('subheading', 'طوابير العمل الموحدة — مؤشرات تشغيلية')
@section('page_actions')
    <a class="btn" href="{{ route('lab.samples.reception') }}"><x-icon name="test-tube" /> استلام عينة</a>
    <a class="btn btn-secondary" href="{{ route('lab.results.entry.index') }}"><x-icon name="flask" /> إدخال النتائج</a>
@endsection
@section('content')
@php
$kpis = [
    ['key' => 'newOrders', 'label' => 'طلبات جديدة', 'icon' => 'clipboard', 'tone' => 'blue', 'href' => route('lab.orders.index', ['status'=>'new']), 'link' => 'فتح الطابور'],
    ['key' => 'awaitingSamples', 'label' => 'بانتظار العينة', 'icon' => 'test-tube', 'tone' => 'cyan', 'href' => route('lab.samples.reception'), 'link' => 'استلام عينة'],
    ['key' => 'inAnalysis', 'label' => 'قيد التحليل', 'icon' => 'flask', 'tone' => 'teal', 'href' => route('lab.orders.index', ['status'=>'in_analysis']), 'link' => 'عرض الطلبات'],
    ['key' => 'needsReview', 'label' => 'بانتظار المراجعة', 'icon' => 'clock', 'tone' => 'amber', 'href' => route('lab.results.review.index'), 'link' => 'قائمة المراجعة'],
    ['key' => 'ready', 'label' => 'جاهز', 'icon' => 'check-circle', 'tone' => 'green', 'href' => route('lab.completed.index'), 'link' => 'المكتملة'],
    ['key' => 'deferred', 'label' => 'مؤجل', 'icon' => 'clock', 'tone' => 'slate', 'href' => route('lab.orders.index', ['status'=>'deferred']), 'link' => 'عرض المؤجلة'],
    ['key' => 'rejected', 'label' => 'مرفوض', 'icon' => 'x', 'tone' => 'red', 'href' => route('lab.orders.index', ['status'=>'rejected']), 'link' => 'عرض المرفوضة'],
    ['key' => 'criticalOpen', 'label' => 'تنبيهات حرجة مفتوحة', 'icon' => 'alert-octagon', 'tone' => 'red', 'href' => route('lab.critical.index'), 'link' => 'التنبيهات الحرجة'],
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
        <div class="card-title"><x-icon name="activity" /> حركة الطلبات — آخر 14 يومًا</div>
        <p class="card-sub">عدد الطلبات الجديدة لكل يوم (حسب تاريخ الطلب)</p>
        <div id="ordersChart" style="height:300px"></div>
    </div>
    <div class="card">
        <div class="card-title"><x-icon name="chart-pie" /> توزيع حالات الطلبات</div>
        <p class="card-sub">حالات الطلبات خلال آخر 30 يومًا</p>
        <div id="statusDonut"></div>
    </div>
</div>

<div class="card">
    <div class="card-title"><x-icon name="activity" /> إجراءات سريعة</div>
    <p class="card-sub">أدوات العمل اليومية للمختبر</p>
    <div class="flex" style="flex-wrap:wrap">
        <a class="btn" href="{{ route('lab.samples.reception') }}"><x-icon name="test-tube" /> استلام عينة</a>
        <a class="btn btn-secondary" href="{{ route('lab.orders.index', ['status'=>'new']) }}"><x-icon name="clipboard" /> الطلبات الجديدة</a>
        <a class="btn btn-secondary" href="{{ route('lab.results.entry.index') }}"><x-icon name="flask" /> إدخال النتائج</a>
        <a class="btn btn-secondary" href="{{ route('lab.results.review.index') }}"><x-icon name="check-circle" /> مراجعة النتائج</a>
        <a class="btn btn-ghost" href="{{ route('lab.critical.index') }}"><x-icon name="alert-octagon" /> التنبيهات الحرجة</a>
        <a class="btn btn-ghost" href="{{ route('lab.attachments.index') }}"><x-icon name="paperclip" /> المرفقات</a>
        <a class="btn btn-ghost" href="{{ route('lab.patients.search') }}"><x-icon name="users" /> بحث المرضى</a>
    </div>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الطلب</th><th>المريض</th><th>الحالة</th><th>تاريخ الطلب</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($recentOrders as $order)
    <tr>
        <td><b>{{ $order->order_number }}</b></td>
        <td>{{ $order->patient->full_name }}</td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td class="muted-cell">{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('lab.orders.show', $order) }}"><x-icon name="eye" /> فتح</a></div></td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><div class="empty-icon"><x-icon name="flask" /></div><h3>لا طلبات قيد التنفيذ</h3><p>لا توجد طلبات حالياً في حالات الطلب أو الاستلام أو التحليل أو المراجعة.</p></div></td></tr>
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
    var statusColors = {
        'requested': '#64748B', 'sample_received': '#0EA5E9', 'in_analysis': '#2563EB',
        'pending_review': '#D97706', 'ready': '#0D9488', 'completed': '#16A34A',
        'archived': '#94A3B8', 'deferred': '#B45309', 'rejected': '#DC2626'
    };
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
