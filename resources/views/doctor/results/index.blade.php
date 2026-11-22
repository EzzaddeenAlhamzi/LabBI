@extends('layouts.admin')
@section('heading', 'نتائج مرضاي')
@section('subheading', 'النتائج المعتمدة لمرضاك')
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الطلب</th><th>المريض</th><th>الفحص</th><th>القيمة</th><th>التصنيف</th><th>الحالة</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($results as $result)
    <tr>
        <td dir="ltr">{{ $result->order->order_number }}</td>
        <td>{{ $result->patient->full_name }}</td>
        <td>{{ $result->test->name_ar }}</td>
        <td dir="ltr"><b>{{ $result->value_numeric }}</b> {{ $result->unit }}</td>
        <td>
            @php
                $ctone = match($result->classification) {
                    'normal' => 'success',
                    'critical','panic','critical_low','critical_high' => 'danger',
                    'borderline','high','low' => 'warning',
                    default => 'neutral',
                };
            @endphp
            <x-status-badge :tone="$ctone" :label="\App\Domain\Critical\ResultClassification::labelAr($result->classification)" />
        </td>
        <td><x-status-badge :status="$result->status" :label="\App\Models\Result::statusLabel($result->status)" /></td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('doctor.results.show', $result) }}"><x-icon name="eye" /> تفاصيل</a></div></td>
    </tr>
    @empty
    <tr><td colspan="7"><div class="empty"><div class="empty-icon"><x-icon name="flask-conical" /></div><h3>لا نتائج بعد</h3><p>لا توجد نتائج معتمدة لمرضاك بعد.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($results->hasPages())<div class="pager">{{ $results->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
