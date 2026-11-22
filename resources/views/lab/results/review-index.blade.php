@extends('layouts.admin')
@section('heading', 'مراجعة النتائج')
@section('subheading', 'قائمة انتظار الاعتماد — يتطلب صلاحية results.approve للاعتماد')
@section('page_actions')
    <a class="btn btn-secondary" href="{{ route('lab.results.entry.index') }}"><x-icon name="flask" /> إدخال النتائج</a>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الطلب</th><th>المريض</th><th>الفحص</th><th>القيمة</th><th>التصنيف</th><th>أدخلها</th><th class="text-end">إجراءات</th></tr></thead>
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
        <td class="muted-cell">{{ $result->enteredBy?->name }}</td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('lab.results.review.show', $result) }}"><x-icon name="eye" /> مراجعة</a></div></td>
    </tr>
    @empty
    <tr><td colspan="7"><div class="empty"><div class="empty-icon"><x-icon name="check-circle" /></div><h3>لا نتائج بانتظار المراجعة</h3><p>لا توجد نتائج بانتظار المراجعة حالياً.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($results->hasPages())<div class="pager">{{ $results->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
