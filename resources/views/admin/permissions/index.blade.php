@extends('layouts.admin')
@section('title', 'الصلاحيات')
@section('heading', 'الصلاحيات')
@section('subheading', 'صلاحيات النظام الممنوحة للأدوار')
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>رمز الصلاحية</th><th>الوصف</th><th>الأدوار الممنوحة</th></tr></thead>
    <tbody>
    @forelse($permissions as $permission)
    <tr>
        <td><code>{{ $permission->code }}</code></td>
        <td class="muted-cell">{{ $permission->description ?: '—' }}</td>
        <td>{{ $permission->roles->pluck('name_ar')->join('، ') ?: '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="3"><div class="empty"><div class="empty-icon"><x-icon name="key" /></div><h3>لا توجد صلاحيات</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
@endsection
