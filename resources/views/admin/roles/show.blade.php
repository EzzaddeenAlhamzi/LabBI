@extends('layouts.admin')
@section('title', 'دور: '.$role->name_ar)
@section('heading', 'دور: '.$role->name_ar)
@section('subheading', 'الصلاحيات الممنوحة لهذا الدور')
@section('page_actions')
    <a class="btn btn-secondary" href="{{ route('admin.roles.index') }}"><x-icon name="chevron-right" /> رجوع للأدوار</a>
@endsection
@section('content')
<div class="card">
    <div class="flex-between mb-2">
        <span class="muted">رمز الدور</span>
        <code style="background:var(--surface-2);padding:.25rem .7rem;border-radius:var(--radius-sm);border:1px solid var(--line)">{{ $role->code }}</code>
    </div>
    <div class="dl">
        <div class="item"><div class="k">عدد المستخدمين</div><div class="v">{{ $role->users->count() }}</div></div>
        <div class="item"><div class="k">عدد الصلاحيات</div><div class="v">{{ $role->permissions->count() }}</div></div>
    </div>
</div>
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>رمز الصلاحية</th><th>الوصف</th></tr></thead>
    <tbody>
    @forelse($role->permissions as $permission)
    <tr>
        <td><code>{{ $permission->code }}</code></td>
        <td class="muted-cell">{{ $permission->description ?: '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="2"><div class="empty"><h3>لا توجد صلاحيات</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
@endsection
