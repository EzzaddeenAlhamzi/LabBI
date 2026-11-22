@extends('layouts.admin')
@section('title', 'فنيو المختبر')
@section('heading', 'فنيو المختبر')
@section('subheading', 'حسابات فنيي المختبر والأقسام')
@section('page_actions')
    <a class="btn" href="{{ route('admin.lab-staff.create') }}"><x-icon name="user-plus" /> إضافة فني</a>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الاسم</th><th>البريد</th><th>المسمى الوظيفي</th><th>القسم</th></tr></thead>
    <tbody>
    @forelse($staff as $row)
    <tr>
        <td><b>{{ $row->user->name }}</b></td>
        <td class="muted-cell" dir="ltr" style="text-align:start">{{ $row->user->email }}</td>
        <td>{{ $row->job_title ?: '—' }}</td>
        <td>{{ $row->department?->name_ar ?: '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="4"><div class="empty"><div class="empty-icon"><x-icon name="microscope" /></div><h3>لا يوجد فنيو مختبر</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($staff->hasPages())<div class="pager">{{ $staff->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
