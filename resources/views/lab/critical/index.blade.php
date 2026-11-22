@extends('layouts.admin')
@section('heading', 'التنبيهات الحرجة')
@section('subheading', 'داخل النظام فقط — بدون واتساب أو بريد في المرحلة 2')
@section('content')
<div class="alert alert-error"><x-icon name="alert-octagon" /><span><b>تنبيهات حرجة</b> — قيم نتائج حرجة تتطلب إقراراً ومتابعة فورية.</span></div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الاكتشاف</th><th>المريض</th><th>الطلب</th><th>الفحص</th><th>القيمة</th><th>العتبات</th><th>الحالة</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($alerts as $alert)
    <tr>
        <td class="muted-cell" dir="ltr">{{ $alert->discovered_at }}</td>
        <td>{{ $alert->patient->full_name }}</td>
        <td dir="ltr">{{ $alert->order->order_number }}</td>
        <td>{{ $alert->test->name_ar }}</td>
        <td dir="ltr"><b>{{ $alert->detected_value }} {{ $alert->unit }}</b></td>
        <td class="muted-cell small" dir="ltr">CL {{ $alert->threshold_snapshot['critical_low'] ?? '—' }} / CH {{ $alert->threshold_snapshot['critical_high'] ?? '—' }}</td>
        <td><x-status-badge :status="$alert->status" :label="$alert->status==='open'?'مفتوح':($alert->status==='acknowledged'?'تم الإقرار':'مغلق')" /></td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('lab.critical.show', $alert) }}"><x-icon name="eye" /> تفاصيل</a></div></td>
    </tr>
    @empty
    <tr><td colspan="8"><div class="empty"><div class="empty-icon"><x-icon name="alert-octagon" /></div><h3>لا توجد تنبيهات</h3><p>لا توجد تنبيهات حرجة حالياً.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($alerts->hasPages())<div class="pager">{{ $alerts->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
