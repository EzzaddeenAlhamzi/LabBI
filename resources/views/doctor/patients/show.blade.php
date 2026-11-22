@extends('layouts.admin')
@section('heading', 'ملف مريض')
@section('subheading', $patient->full_name.' — '.$patient->medical_file_number)
@section('page_actions')
    <a class="btn" href="{{ route('doctor.orders.create') }}"><x-icon name="plus" /> إنشاء طلب</a>
    <a class="btn btn-ghost" href="{{ route('doctor.patients.index') }}"><x-icon name="chevron-right" /> مرضاي</a>
@endsection
@section('content')
<div class="card">
    <div class="card-title"><x-icon name="patients" /> بيانات المريض</div>
    <div class="dl">
        <div class="item"><div class="k">الاسم</div><div class="v">{{ $patient->full_name }}</div></div>
        <div class="item"><div class="k">الرقم الطبي</div><div class="v" dir="ltr">{{ $patient->medical_file_number }}</div></div>
        <div class="item"><div class="k">الهاتف</div><div class="v" dir="ltr">{{ $patient->phone ?? '—' }}</div></div>
        <div class="item"><div class="k">عدد الطلبات</div><div class="v">{{ $patient->orders->count() }}</div></div>
    </div>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>رقم الطلب</th><th>الحالة</th><th>الفحوصات</th><th>التاريخ</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($patient->orders as $order)
    <tr>
        <td dir="ltr"><b>{{ $order->order_number }}</b></td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td class="muted-cell">{{ $order->items->pluck('test.name_ar')->join('، ') }}</td>
        <td class="muted-cell" dir="ltr">{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('doctor.orders.show', $order) }}"><x-icon name="eye" /> فتح</a></div></td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><div class="empty-icon"><x-icon name="clipboard" /></div><h3>لا طلبات</h3><p>لا توجد طلبات لهذا المريض.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
@endsection
