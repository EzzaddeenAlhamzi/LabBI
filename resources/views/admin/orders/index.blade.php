@extends('layouts.admin')
@section('title', 'طلبات الفحص')
@section('heading', 'طلبات الفحص')
@section('subheading', 'جميع طلبات الفحوصات المخبرية في النظام')
@section('page_actions')
    <a class="btn" href="{{ route('admin.orders.create') }}"><x-icon name="plus" /> طلب جديد</a>
@endsection
@section('content')
<div class="card">
    <form method="get" class="flex" style="gap:.6rem;flex-wrap:wrap">
        <div class="field" style="flex:1;min-width:260px;margin-bottom:0">
            <label for="q" class="sr-only" style="position:absolute">بحث</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="رقم الطلب / المريض" style="margin:0">
        </div>
        <button class="btn" type="submit"><x-icon name="search" /> بحث</button>
        @if(request('q'))<a class="btn btn-ghost" href="{{ route('admin.orders.index') }}">مسح</a>@endif
    </form>
</div>
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>رقم الطلب</th><th>المريض</th><th>الطبيب</th><th>الحالة</th><th>تاريخ الطلب</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($orders as $order)
    <tr>
        <td><a href="{{ route('admin.orders.show', $order) }}"><code>{{ $order->order_number }}</code></a></td>
        <td><b>{{ $order->patient->full_name }}</b><div class="muted-cell small">{{ $order->patient->medical_file_number }}</div></td>
        <td>{{ $order->doctor->user->name }}</td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td class="muted-cell">{{ $order->ordered_at->format('Y-m-d H:i') }}</td>
        <td><div class="row-actions"><a class="btn btn-ghost btn-sm" href="{{ route('admin.orders.show', $order) }}"><x-icon name="eye" /> عرض</a></div></td>
    </tr>
    @empty
    <tr><td colspan="6"><div class="empty"><div class="empty-icon"><x-icon name="clipboard" /></div><h3>لا توجد طلبات</h3><p>لا توجد طلبات فحص مطابقة.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($orders->hasPages())<div class="pager">{{ $orders->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
