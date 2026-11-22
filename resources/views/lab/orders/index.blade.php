@extends('layouts.admin')
@section('heading', 'طلبات المختبر')
@section('subheading', 'قائمة طلبات المختبر مع تصفية حسب الحالة')
@section('page_actions')
    <a class="btn" href="{{ route('lab.samples.reception') }}"><x-icon name="test-tube" /> استلام عينة</a>
@endsection
@section('content')
<div class="card">
    <form method="get" class="form-grid" style="grid-template-columns: 1fr auto; align-items:end">
        <div class="field" style="margin-bottom:0">
            <label for="status">الحالة</label>
            <select id="status" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                <option value="new" @selected(($statusFilter ?? '') === 'new')>جديدة فقط</option>
                @foreach(\App\Domain\Workflow\OrderStatus::all() as $s)
                    @if($s !== 'requested')
                        <option value="{{ $s }}" @selected(($statusFilter ?? '') === $s)>{{ \App\Models\TestOrder::statusLabel($s) }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="flex" style="margin-bottom:0">
            @if(!empty($statusFilter))<a class="btn btn-ghost" href="{{ route('lab.orders.index') }}">إعادة تعيين</a>@endif
        </div>
    </form>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>رقم الطلب</th><th>المريض</th><th>الطبيب</th><th>الحالة</th><th>تاريخ الطلب</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($orders as $order)
    <tr>
        <td><b>{{ $order->order_number }}</b></td>
        <td>{{ $order->patient->full_name }}</td>
        <td class="muted-cell">{{ $order->doctor->user->name }}</td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td class="muted-cell">{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('lab.orders.show', $order) }}"><x-icon name="eye" /> فتح</a></div></td>
    </tr>
    @empty
    <tr><td colspan="6"><div class="empty"><div class="empty-icon"><x-icon name="clipboard" /></div><h3>لا توجد طلبات</h3><p>لا توجد طلبات مطابقة لمعايير البحث الحالية.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($orders->hasPages())<div class="pager">{{ $orders->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
