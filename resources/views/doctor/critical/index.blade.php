@extends('layouts.admin')
@section('heading', 'النتائج الحرجة')
@section('subheading', 'تنبيهات داخل النظام فقط')
@section('content')
<div class="alert alert-error"><x-icon name="alert-octagon" /><span><b>نتائج حرجة</b> — قيم تتجاوز العتبات الحرجة وتتطلب متابعة سريرية فورية.</span></div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الاكتشاف</th><th>المريض</th><th>الفحص</th><th>القيمة</th><th>العتبات</th><th>الحالة</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($alerts as $alert)
    <tr>
        <td class="muted-cell" dir="ltr">{{ $alert->discovered_at }}</td>
        <td>{{ $alert->patient->full_name }}</td>
        <td>{{ $alert->test->name_ar }}</td>
        <td dir="ltr"><b>{{ $alert->detected_value }} {{ $alert->unit }}</b></td>
        <td class="muted-cell small" dir="ltr">CL {{ $alert->threshold_snapshot['critical_low'] ?? '—' }} / CH {{ $alert->threshold_snapshot['critical_high'] ?? '—' }}</td>
        <td><x-status-badge :status="$alert->status" :label="$alert->status==='open'?'مفتوح':($alert->status==='acknowledged'?'تم الإقرار':'مغلق')" /></td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('doctor.results.show', $alert->result) }}"><x-icon name="eye" /> النتيجة</a></div></td>
    </tr>
    @empty
    <tr><td colspan="7"><div class="empty"><div class="empty-icon"><x-icon name="alert-octagon" /></div><h3>لا تنبيهات حرجة</h3><p>لا توجد نتائج حرجة حالياً.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($alerts->hasPages())<div class="pager">{{ $alerts->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
