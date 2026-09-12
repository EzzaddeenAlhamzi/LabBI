@extends('layouts.app')
@section('heading', 'إنشاء ملف مريض')
@section('content')
<div class="card">
<form method="post" action="{{ route('admin.patients.store') }}">@csrf
<div class="grid-2">
<div><label>الاسم الكامل</label><input name="full_name" value="{{ old('full_name') }}" required></div>
<div><label>الرقم الطبي (اختياري — يُولَّد تلقائياً)</label><input name="medical_file_number" value="{{ old('medical_file_number') }}"></div>
<div><label>تاريخ الميلاد</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"></div>
<div><label>الجنس</label><select name="sex"><option value="">—</option><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
<div><label>الهاتف</label><input name="phone" value="{{ old('phone') }}"></div>
<div><label>الطبيب المعالج</label>
<select name="primary_doctor_id"><option value="">—</option>
@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->user->name }}</option>@endforeach
</select></div>
</div>
<label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="create_login" value="1" style="width:auto;margin:0"> إنشاء حساب دخول للمريض</label>
<div class="grid-2">
<div><label>بريد الدخول</label><input type="email" name="email" value="{{ old('email') }}"></div>
<div><label>كلمة المرور</label><input type="password" name="password"></div>
</div>
<button class="btn" type="submit">حفظ</button>
</form>
</div>
@endsection