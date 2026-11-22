@extends('layouts.admin')
@section('heading', 'إدخال النتائج')
@section('subheading', 'اختر طلباً لإدخال أو تعديل النتائج قبل الاعتماد')
@section('page_actions')
    <a class="btn btn-secondary" href="{{ route('lab.results.review.index') }}"><x-icon name="check-circle" /> مراجعة النتائج</a>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الطلب</th><th>المريض</th><th>الحالة</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($orders as $order)
    <tr>
        <td dir="ltr"><b>{{ $order->order_number }}</b></td>
        <td>{{ $order->patient->full_name }}</td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('lab.results.entry', $order) }}"><x-icon name="flask" /> فتح</a></div></td>
    </tr>
    @empty
    <tr><td colspan="4"><div class="empty"><div class="empty-icon"><x-icon name="flask" /></div><h3>لا توجد طلبات جاهزة</h3><p>لا توجد طلبات جاهزة لإدخال النتائج.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($orders->hasPages())<div class="pager">{{ $orders->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
