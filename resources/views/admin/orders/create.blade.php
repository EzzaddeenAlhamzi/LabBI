@extends('layouts.app')
@section('heading', 'إنشاء طلب فحص')
@section('content')
<div class="card"><form method="post" action="{{ route('admin.orders.store') }}">@csrf
<label>المريض</label>
<select name="patient_id" required>@foreach($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->medical_file_number }} — {{ $patient->full_name }}</option>@endforeach</select>
<label>الطبيب</label>
<select name="doctor_id" required>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->user->name }}</option>@endforeach</select>
<label>الفحوصات</label>
@foreach($tests as $test)
<label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="test_ids[]" value="{{ $test->id }}" style="width:auto;margin:0"> {{ $test->code }} — {{ $test->name_ar }}</label>
@endforeach
<label>ملاحظات</label><textarea name="notes" rows="3"></textarea>
<button class="btn" type="submit">إنشاء وإرسال للمختبر</button>
</form></div>
@endsection