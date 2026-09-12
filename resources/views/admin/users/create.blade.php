@extends('layouts.app')
@section('heading', 'إنشاء مستخدم')
@section('content')
<div class="card">
<form method="post" action="{{ route('admin.users.store') }}">@csrf
<label>الاسم</label><input name="name" value="{{ old('name') }}" required>
<label>البريد</label><input type="email" name="email" value="{{ old('email') }}" required>
<label>الهاتف</label><input name="phone" value="{{ old('phone') }}">
<label>كلمة المرور</label><input type="password" name="password" required>
<label>الدور</label>
<select name="role_id" required>
@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name_ar }}</option>@endforeach
</select>
<label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="is_active" value="1" checked style="width:auto;margin:0"> نشط</label>
<button class="btn" type="submit">حفظ</button>
</form>
</div>
@endsection