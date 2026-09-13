@extends('layouts.app')
@section('heading', 'التقارير التشغيلية')
@section('subheading', 'تقارير تصفية من الخادم — منفصلة عن تقارير PDF السريرية')
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
            <button class="btn" type="submit">عرض التقرير</button>
        </div>
    </form>
</div>

<div class="stats" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr))">
    <div class="stat"><span class="muted">عدد الطلبات</span><strong>{{ $summary['orders_count'] }}</strong></div>
    <div class="stat"><span class="muted">نتائج معتمدة</span><strong>{{ $summary['approved_results'] }}</strong></div>
    <div class="stat"><span class="muted">مرتبط بحرج</span><strong>{{ $summary['critical_linked'] }}</strong></div>
    <div class="stat"><span class="muted">متوسط TAT</span><strong>{{ $summary['avg_order_tat_hours'] ?? '—' }}</strong></div>
</div>

@if($volume->isNotEmpty())
<div class="card">
    <h3>توزيع الحالات (من نفس مقاييس الخادم)</h3>
    <table>
        <thead><tr><th>الحالة</th><th>العدد</th></tr></thead>
        <tbody>
            @foreach($volume as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="card">
    <h3>تفاصيل الطلبات</h3>
    <table>
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
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->patient?->full_name }}</td>
                    <td>{{ $order->doctor?->user?->name }}</td>
                    <td><span class="badge">{{ \App\Models\TestOrder::statusLabel($order->status) }}</span></td>
                    <td>{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $row['tat_hours'] ?? 'غير متاح' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">لا توجد طلبات مطابقة.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:1rem">{{ $rows->links() }}</div>
</div>
@endsection
