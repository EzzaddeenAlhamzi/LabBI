@extends('layouts.app')
@section('heading', 'تعديل مريض')
@section('content')
<div class="card">
<form method="post" action="{{ route('admin.patients.update', $patient) }}">@csrf @method('PUT')
<label>الاسم</label><input name="full_name" value="{{ old('full_name', $patient->full_name) }}" required>
<label>تاريخ الميلاد</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($patient->date_of_birth)->format('Y-m-d')) }}">
<label>الجنس</label><select name="sex"><option value="male" @selected($patient->sex==='male')>ذكر</option><option value="female" @selected($patient->sex==='female')>أنثى</option></select>
<label>الهاتف</label><input name="phone" value="{{ old('phone', $patient->phone) }}">
<label>الطبيب</label>
<select name="primary_doctor_id"><option value="">—</option>
@foreach($doctors as $doctor)
<option value="{{ $doctor->id }}" @selected($patient->primary_doctor_id==$doctor->id)>{{ $doctor->user->name }}</option>
@endforeach
</select>
<button class="btn" type="submit">تحديث</button>
</form>
</div>
@endsection