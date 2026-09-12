@extends('layouts.app')
@section('heading', 'المستخدمون')
@section('content')
<div class="card">
    <form method="get" class="grid-2">
        <div><label>بحث</label><input name="q" value="{{ request('q') }}" placeholder="الاسم أو البريد"></div>
        <div>
            <label>الدور</label>
            <select name="role">
                <option value="">الكل</option>
                @foreach($roles as $role)
                    <option value="{{ $role->code }}" @selected(request('role')===$role->code)>{{ $role->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions" style="grid-column:1/-1">
            <button class="btn" type="submit">تصفية</button>
            <a class="btn" href="{{ route('admin.users.create') }}">إنشاء مستخدم</a>
        </div>
    </form>
</div>
<div class="card">
<table>
<thead><tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th></th></tr></thead>
<tbody>
@foreach($users as $user)
<tr>
<td>{{ $user->name }}</td>
<td>{{ $user->email }}</td>
<td>{{ $user->roles->pluck('name_ar')->join('، ') }}</td>
<td>{{ $user->is_active ? 'نشط' : 'معطّل' }}</td>
<td>{{ optional($user->last_login_at)->format('Y-m-d H:i') ?: '—' }}</td>
<td class="actions">
<a href="{{ route('admin.users.edit', $user) }}">تعديل</a>
<form method="post" action="{{ route('admin.users.toggle', $user) }}">@csrf<button class="btn btn-ghost" type="submit">{{ $user->is_active ? 'تعطيل' : 'تفعيل' }}</button></form>
</td>
</tr>
@endforeach
</tbody>
</table>
{{ $users->links() }}
</div>
@endsection