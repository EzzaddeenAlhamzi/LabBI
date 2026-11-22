@extends('layouts.admin')
@section('title', 'الأدوار')
@section('heading', 'الأدوار والصلاحيات')
@section('subheading', 'أربعة أدوار أساسية في الإصدار الأول — بدون موظف استقبال')
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الرمز</th><th>اسم الدور</th><th class="num">المستخدمون</th><th class="num">الصلاحيات</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($roles as $role)
    <tr>
        <td><code class="badge badge-neutral" style="font-family:var(--font-mono)">{{ $role->code }}</code></td>
        <td><b>{{ $role->name_ar }}</b></td>
        <td class="num">{{ $role->users_count }}</td>
        <td class="num">{{ $role->permissions_count }}</td>
        <td><div class="row-actions"><a class="btn btn-ghost btn-sm" href="{{ route('admin.roles.show', $role) }}"><x-icon name="eye" /> عرض</a></div></td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><div class="empty-icon"><x-icon name="shield" /></div><h3>لا توجد أدوار</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
@endsection
