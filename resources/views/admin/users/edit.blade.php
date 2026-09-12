@extends('layouts.app')
@section('heading', 'تعديل مستخدم')
@section('content')
<div class="card">
<form method="post" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')
<label>الاسم</label><input name="name" value="{{ old('name', $user->name) }}" required>
<label>البريد</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required>
<label>الهاتف</label><input name="phone" value="{{ old('phone', $user->phone) }}">
<label>كلمة مرور جديدة (اختياري)</label><input type="password" name="password">
<label>الدور</label>
<select name="role_id" required>
@foreach($roles as $role)
<option value="{{ $role->id }}" @selected($user->roles->first()?->id === $role->id)>{{ $role->name_ar }}</option>
@endforeach
</select>
<label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) style="width:auto;margin:0"> نشط</label>
<button class="btn" type="submit">تحديث</button>
</form>
</div>
@endsection