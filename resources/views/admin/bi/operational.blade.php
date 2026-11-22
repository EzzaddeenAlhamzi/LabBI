@extends('layouts.admin')
@section('title', 'التقارير التشغيلية')
@section('heading', 'التقارير التشغيلية')
@section('subheading', 'تقارير تصفية من الخادم — منفصلة عن تقارير PDF السريرية')
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
            <button class="btn" type="submit"><x-icon name="file-text" /> عرض التقرير</button>
        </div>
    </form>
</div>

<div class="kpi-grid">
    <div class="kpi"><div class="kpi-top"><span class="kpi-label">عدد الطلبات</span><span class="kpi-icon tone-blue"><x-icon name="clipboard" /></span></div><div class="kpi-value">{{ number_format($summary['orders_count']) }}</div></div>
    <div class="kpi"><div class="kpi-top"><span class="kpi-label">نتائج معتمدة</span><span class="kpi-icon tone-green"><x-icon name="check-circle" /></span></div><div class="kpi-value">{{ number_format($summary['approved_results']) }}</div></div>
    <div class="kpi"><div class="kpi-top"><span class="kpi-label">مرتبط بحرج</span><span class="kpi-icon tone-red"><x-icon name="alert-octagon" /></span></div><div class="kpi-value">{{ number_format($summary['critical_linked']) }}</div></div>
    <div class="kpi"><div class="kpi-top"><span class="kpi-label">متوسط TAT (ساعة)</span><span class="kpi-icon tone-purple"><x-icon name="timer" /></span></div><div class="kpi-value">{{ $summary['avg_order_tat_hours'] ?? '—' }}</div></div>
</div>

@if($volume->isNotEmpty())
<div class="grid-2" style="align-items:start">
    <div class="card">
        <div class="card-title"><x-icon name="chart-pie" /> توزيع حالات الطلبات</div>
        <p class="card-sub">من نفس مقاييس الخادم — دون إعادة حساب</p>
        <div id="statusDonut"></div>
    </div>
    <div class="card flush">
        <div class="table-wrap">
        <table class="data">
            <thead><tr><th>الحالة</th><th class="num">العدد</th></tr></thead>
            <tbody>
                @foreach($volume as $row)
                    <tr><td>{{ $row['label'] }}</td><td class="num"><b>{{ $row['count'] }}</b></td></tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
@endif

<div class="card flush">
    <div class="card-title" style="padding:1rem 1rem 0"><x-icon name="clipboard" /> تفاصيل الطلبات ({{ $rows->total() }})</div>
    <div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>رقم الطلب</th>
                <th>المريض</th>
                <th>الطبيب</th>
                <th>الحالة</th>
                <th>تاريخ الطلب</th>
                <th>TAT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @php($order = $row['order'])
                <tr>
                    <td><a href="{{ route('admin.orders.show', $order) }}"><code>{{ $order->order_number }}</code></a></td>
                    <td><b>{{ $order->patient?->full_name }}</b></td>
                    <td>{{ $order->doctor?->user?->name }}</td>
                    <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
                    <td class="muted-cell">{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
                    <td class="num">{{ $row['tat_hours'] ?? 'غير متاح' }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><div class="empty-icon"><x-icon name="clipboard" /></div><h3>لا توجد طلبات</h3><p>لا توجد طلبات مطابقة لمعايير التصفية.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">{{ $rows->links('admin.partials.pagination') }}</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('statusDonut');
    if (el && window.LabBICharts) {
        var colors = { 'requested':'#64748B','sample_received':'#0EA5E9','in_analysis':'#2563EB','pending_review':'#D97706','ready':'#0D9488','completed':'#16A34A','archived':'#94A3B8','deferred':'#B45309','rejected':'#DC2626' };
        LabBICharts.donut(el, {
            data: @json($volume->map(fn($v) => ['label' => $v['label'], 'value' => $v['count'], 'color' => $colors[$v['status']] ?? '#64748B'])->values()),
            centerLabel: 'إجمالي الطلبات',
            centerValue: {{ $summary['orders_count'] }}
        });
    }
});
</script>
@endpush
