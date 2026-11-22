@extends('layouts.admin')
@section('heading', 'طلباتي')
@section('subheading', 'جميع طلبات الفحص التي أنشأتها')
@section('page_actions')
    <a class="btn" href="{{ route('doctor.orders.create') }}"><x-icon name="plus" /> طلب جديد</a>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>رقم الطلب</th><th>المريض</th><th>الحالة</th><th>التاريخ</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($orders as $order)
    <tr>
        <td dir="ltr"><a href="{{ route('doctor.orders.show', $order) }}"><b>{{ $order->order_number }}</b></a></td>
        <td>{{ $order->patient->full_name }}</td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td class="muted-cell" dir="ltr">{{ $order->ordered_at->format('Y-m-d H:i') }}</td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('doctor.orders.show', $order) }}"><x-icon name="eye" /> فتح</a></div></td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><div class="empty-icon"><x-icon name="clipboard" /></div><h3>لا طلبات</h3><p>لم تنشئ أي طلبات فحص بعد.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($orders->hasPages())<div class="pager">{{ $orders->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
