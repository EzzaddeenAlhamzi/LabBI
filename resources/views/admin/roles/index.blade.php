@extends('layouts.app')
@section('heading', 'الأدوار')
@section('subheading', 'أربعة أدوار فقط في الإصدار الأول — بدون موظف استقبال')
@section('content')
<div class="card">
<table>
<thead><tr><th>الرمز</th><th>الاسم</th><th>المستخدمون</th><th>الصلاحيات</th><th></th></tr></thead>
<tbody>
@foreach($roles as $role)
<tr>
<td><code>{{ $role->code }}</code></td>
<td>{{ $role->name_ar }}</td>
<td>{{ $role->users_count }}</td>
<td>{{ $role->permissions_count }}</td>
<td><a href="{{ route('admin.roles.show', $role) }}">عرض</a></td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection