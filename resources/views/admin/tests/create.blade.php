@extends('layouts.app')
@section('heading', 'إضافة فحص')
@section('content')
<div class="card"><form method="post" action="{{ route('admin.tests.store') }}">@csrf
<label>الرمز</label><input name="code" required>
<label>الاسم بالعربية</label><input name="name_ar" required>
<label>الاسم بالإنجليزية</label><input name="name_en">
<label>الوحدة</label><input name="unit">
<label>القسم</label><select name="department_id"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name_ar }}</option>@endforeach</select>
<button class="btn" type="submit">حفظ</button>
</form></div>
@endsection