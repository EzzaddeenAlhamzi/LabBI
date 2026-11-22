@extends('layouts.admin')
@section('title', 'ملف المريض')
@section('heading', $patient->full_name)
@section('subheading', 'الرقم الطبي: '.$patient->medical_file_number)
@section('page_actions')
    <a class="btn btn-secondary" href="{{ route('admin.patients.index') }}"><x-icon name="chevron-right" /> رجوع</a>
    <a class="btn" href="{{ route('admin.patients.edit', $patient) }}"><x-icon name="pencil" /> تعديل الملف</a>
@endsection
@section('content')
<div class="card">
    <div class="card-title"><x-icon name="patients" /> البيانات الأساسية</div>
    <div class="dl">
        <div class="item"><div class="k">الرقم الطبي</div><div class="v"><code>{{ $patient->medical_file_number }}</code></div></div>
        <div class="item"><div class="k">الاسم الكامل</div><div class="v">{{ $patient->full_name }}</div></div>
        <div class="item"><div class="k">تاريخ الميلاد</div><div class="v">{{ optional($patient->date_of_birth)->format('Y-m-d') ?: '—' }}</div></div>
        <div class="item"><div class="k">الجنس</div><div class="v">{{ $patient->sex === 'male' ? 'ذكر' : ($patient->sex === 'female' ? 'أنثى' : '—') }}</div></div>
        <div class="item"><div class="k">الهاتف</div><div class="v" dir="ltr" style="text-align:start">{{ $patient->phone ?: '—' }}</div></div>
        <div class="item"><div class="k">الطبيب المعالج</div><div class="v">{{ $patient->primaryDoctor?->user?->name ?: '—' }}</div></div>
    </div>
</div>
<div class="card flush">
    <div class="card-title" style="padding:1rem 1rem 0"><x-icon name="clipboard" /> الطلبات المرتبطة ({{ $patient->orders->count() }})</div>
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>رقم الطلب</th><th>الحالة</th><th>التاريخ</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($patient->orders as $order)
    <tr>
        <td><a href="{{ route('admin.orders.show', $order) }}"><code>{{ $order->order_number }}</code></a></td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td class="muted-cell">{{ $order->ordered_at->format('Y-m-d H:i') }}</td>
        <td><div class="row-actions"><a class="btn btn-ghost btn-sm" href="{{ route('admin.orders.show', $order) }}"><x-icon name="eye" /> عرض</a></div></td>
    </tr>
    @empty
    <tr><td colspan="4"><div class="empty"><h3>لا توجد طلبات</h3><p>لم يتم إنشاء أي طلبات فحص لهذا المريض بعد.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
@endsection
