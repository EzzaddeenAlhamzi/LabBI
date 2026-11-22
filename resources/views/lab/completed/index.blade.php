@extends('layouts.admin')
@section('heading', 'الفحوصات المكتملة')
@section('subheading', 'الطلبات الجاهزة والمكتملة والمؤرشفة وأحدث النتائج المعتمدة')
@section('page_actions')
    <a class="btn btn-ghost" href="{{ route('lab.orders.index') }}"><x-icon name="clipboard" /> كل الطلبات</a>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الطلب</th><th>المريض</th><th>الحالة</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($orders as $order)
    <tr>
        <td dir="ltr"><a href="{{ route('lab.orders.show', $order) }}"><b>{{ $order->order_number }}</b></a></td>
        <td>{{ $order->patient->full_name }}</td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('lab.orders.show', $order) }}"><x-icon name="eye" /> فتح</a></div></td>
    </tr>
    @empty
    <tr><td colspan="4"><div class="empty"><div class="empty-icon"><x-icon name="check-circle" /></div><h3>لا توجد طلبات مكتملة</h3><p>لا توجد طلبات في حالات الجاهز/المكتمل/المؤرشف.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($orders->hasPages())<div class="pager">{{ $orders->links('admin.partials.pagination') }}</div>@endif
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>المريض</th><th>الفحص</th><th>القيمة</th><th>التصنيف</th><th>الاعتماد</th></tr></thead>
    <tbody>
    @forelse($results as $result)
    <tr>
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
        <td class="muted-cell" dir="ltr">{{ $result->approved_at }}</td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><div class="empty-icon"><x-icon name="flask" /></div><h3>لا نتائج معتمدة</h3><p>لا توجد نتائج معتمدة بعد.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
@endsection
