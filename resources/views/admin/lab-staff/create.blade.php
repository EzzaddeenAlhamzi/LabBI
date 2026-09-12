@extends('layouts.app')
@section('heading', 'إضافة فني مختبر')
@section('content')
<div class="card"><form method="post" action="{{ route('admin.lab-staff.store') }}">@csrf
<label>الاسم</label><input name="name" required>
<label>البريد</label><input type="email" name="email" required>
<label>الهاتف</label><input name="phone">
<label>كلمة المرور</label><input type="password" name="password" required>
<label>المسمى الوظيفي</label><input name="job_title">
<label>القسم</label><select name="department_id"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name_ar }}</option>@endforeach</select>
<button class="btn" type="submit">حفظ</button>
</form></div>
@endsection