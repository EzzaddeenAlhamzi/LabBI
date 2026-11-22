@extends('layouts.admin')
@section('title', 'المرضى')
@section('heading', 'إدارة المرضى')
@section('subheading', 'ملفات المرضى والبيانات السريرية الأساسية')
@section('page_actions')
    <a class="btn" href="{{ route('admin.patients.create') }}"><x-icon name="user-plus" /> مريض جديد</a>
@endsection
@section('content')
<div class="card">
    <form method="get" class="flex" style="gap:.6rem;flex-wrap:wrap">
        <div class="field" style="flex:1;min-width:260px;margin-bottom:0">
            <label for="q" class="sr-only" style="position:absolute">بحث</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="الرقم الطبي / الاسم / الهاتف" style="margin:0">
        </div>
        <button class="btn" type="submit"><x-icon name="search" /> بحث</button>
        @if(request('q'))<a class="btn btn-ghost" href="{{ route('admin.patients.index') }}">مسح</a>@endif
    </form>
</div>
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الرقم الطبي</th><th>المريض</th><th>الهاتف</th><th>الطبيب المعالج</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($patients as $patient)
    <tr>
        <td><code>{{ $patient->medical_file_number }}</code></td>
        <td><b>{{ $patient->full_name }}</b></td>
        <td class="muted-cell" dir="ltr" style="text-align:start">{{ $patient->phone ?: '—' }}</td>
        <td>{{ $patient->primaryDoctor?->user?->name ?: '—' }}</td>
        <td><div class="row-actions"><a class="btn btn-ghost btn-sm" href="{{ route('admin.patients.show', $patient) }}"><x-icon name="eye" /> تفاصيل</a></div></td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><div class="empty-icon"><x-icon name="patients" /></div><h3>لا يوجد مرضى</h3><p>لا توجد ملفات مرضى مطابقة لمعايير البحث.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($patients->hasPages())<div class="pager">{{ $patients->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
