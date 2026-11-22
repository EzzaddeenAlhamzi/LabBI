@extends('layouts.admin')
@section('heading', 'مرضاي')
@section('subheading', 'المرضى المرتبطون بحسابك كطبيب معالج')
@section('page_actions')
    <a class="btn" href="{{ route('doctor.orders.create') }}"><x-icon name="plus" /> طلب فحص جديد</a>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الرقم الطبي</th><th>الاسم</th><th>الهاتف</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($patients as $patient)
    <tr>
        <td dir="ltr">{{ $patient->medical_file_number }}</td>
        <td><b>{{ $patient->full_name }}</b></td>
        <td class="muted-cell" dir="ltr">{{ $patient->phone }}</td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('doctor.patients.show', $patient) }}"><x-icon name="eye" /> عرض</a></div></td>
    </tr>
    @empty
    <tr><td colspan="4"><div class="empty"><div class="empty-icon"><x-icon name="patients" /></div><h3>لا مرضى</h3><p>لا يوجد مرضى مرتبطون بحسابك بعد.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($patients->hasPages())<div class="pager">{{ $patients->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
