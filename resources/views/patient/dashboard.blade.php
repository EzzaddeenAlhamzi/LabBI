@extends('layouts.admin')
@section('heading', 'لوحة المريض')
@section('subheading', 'متابعة طلبات الفحوصات والنتائج والتقارير المتاحة')
@section('page_actions')
    <a class="btn" href="{{ route('patient.tests.index') }}"><x-icon name="flask-conical" /> فحوصاتي ونتائجي</a>
    <a class="btn btn-secondary" href="{{ route('patient.requests.index') }}"><x-icon name="mail" /> طلباتي للطبيب</a>
@endsection
@section('content')
@php
$orders = $patient->orders;
$totalOrders = $orders->count();
$inProgress = $orders->whereNotIn('status', ['completed','archived','rejected'])->count();
$completedOrders = $orders->whereIn('status', ['completed','ready'])->count();
$statusColors = [
    'requested' => '#64748B', 'sample_received' => '#0EA5E9', 'in_analysis' => '#2563EB',
    'pending_review' => '#D97706', 'ready' => '#0D9488', 'completed' => '#16A34A',
    'archived' => '#94A3B8', 'deferred' => '#B45309', 'rejected' => '#DC2626',
];
$kpis = [
    ['label' => 'إجمالي طلباتي', 'value' => $totalOrders, 'icon' => 'clipboard', 'tone' => 'blue', 'href' => route('patient.tests.index'), 'link' => 'فحوصاتي'],
    ['label' => 'طلبات قيد التنفيذ', 'value' => $inProgress, 'icon' => 'activity', 'tone' => 'amber', 'href' => route('patient.tests.index'), 'link' => 'متابعة'],
    ['label' => 'طلبات مكتملة', 'value' => $completedOrders, 'icon' => 'check-circle', 'tone' => 'green', 'href' => route('patient.tests.index'), 'link' => 'النتائج'],
    ['label' => 'نتائج معتمدة', 'value' => $approvedResults, 'icon' => 'flask-conical', 'tone' => 'teal', 'href' => route('patient.tests.index'), 'link' => 'عرض'],
];
@endphp

<div class="card">
    <div class="flex-between">
        <div>
            <div class="card-title" style="margin-bottom:.2rem"><x-icon name="heart-pulse" /> مرحباً {{ $patient->full_name }}</div>
            <p class="card-sub">الرقم الطبي: <b dir="ltr">{{ $patient->medical_file_number }}</b> · الطبيب المعالج: {{ $patient->primaryDoctor?->user?->name ?: '—' }}</p>
        </div>
    </div>
</div>

<div class="kpi-grid">
    @foreach($kpis as $kpi)
    <div class="kpi">
        <div class="kpi-top">
            <span class="kpi-label">{{ $kpi['label'] }}</span>
            <span class="kpi-icon tone-{{ $kpi['tone'] }}"><x-icon :name="$kpi['icon']" /></span>
        </div>
        <div class="kpi-value">{{ number_format($kpi['value']) }}</div>
        <div class="kpi-foot"><span class="kpi-note">{{ now()->format('Y-m-d') }}</span><a class="small" href="{{ $kpi['href'] }}">{{ $kpi['link'] }} ←</a></div>
    </div>
    @endforeach
</div>

<div class="grid-3" style="grid-template-columns: 2fr 1fr; align-items:start">
    <div class="card">
        <div class="card-title"><x-icon name="activity" /> طلباتي — آخر 14 يومًا</div>
        <p class="card-sub">عدد طلبات الفحص الخاصة بك لكل يوم</p>
        <div id="ordersChart" style="height:280px"></div>
    </div>
    <div class="card">
        <div class="card-title"><x-icon name="chart-pie" /> حالات طلباتي</div>
        <p class="card-sub">توزيع حالات طلباتك خلال آخر 30 يومًا</p>
        <div id="statusDonut"></div>
    </div>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الطلب</th><th>الحالة</th><th>التاريخ</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($orders->sortByDesc('ordered_at')->take(10) as $order)
    <tr>
        <td dir="ltr"><b>{{ $order->order_number }}</b></td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td class="muted-cell" dir="ltr">{{ $order->ordered_at?->format('Y-m-d') }}</td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('patient.tests.index') }}"><x-icon name="eye" /> عرض</a></div></td>
    </tr>
    @empty
    <tr><td colspan="4"><div class="empty"><div class="empty-icon"><x-icon name="clipboard" /></div><h3>لا طلبات بعد</h3><p>لم يتم إنشاء أي طلبات فحص بعد.</p></div></td></tr>
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
            height: 280
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
