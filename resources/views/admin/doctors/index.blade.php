@extends('layouts.admin')
@section('title', 'الأطباء')
@section('heading', 'إدارة الأطباء')
@section('subheading', 'حسابات الأطباء والتخصصات والأقسام')
@section('page_actions')
    <a class="btn" href="{{ route('admin.doctors.create') }}"><x-icon name="user-plus" /> إضافة طبيب</a>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الاسم</th><th>البريد</th><th>التخصص</th><th>رقم الترخيص</th><th>القسم</th></tr></thead>
    <tbody>
    @forelse($doctors as $doctor)
    <tr>
        <td><b>{{ $doctor->user->name }}</b></td>
        <td class="muted-cell" dir="ltr" style="text-align:start">{{ $doctor->user->email }}</td>
        <td>{{ $doctor->specialty ?: '—' }}</td>
        <td><code>{{ $doctor->license_number ?: '—' }}</code></td>
        <td>{{ $doctor->department?->name_ar ?: '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><div class="empty-icon"><x-icon name="doctor" /></div><h3>لا يوجد أطباء</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($doctors->hasPages())<div class="pager">{{ $doctors->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
