@extends('layouts.admin')
@section('title', 'تعديل مريض')
@section('heading', 'تعديل ملف المريض')
@section('subheading', $patient->full_name.' — '.$patient->medical_file_number)
@section('content')
<div class="card" style="max-width:720px">
    <form method="post" action="{{ route('admin.patients.update', $patient) }}">@csrf @method('PUT')
        <div class="form-grid">
            <div class="field">
                <label for="full_name">الاسم الكامل <span class="req">*</span></label>
                <input id="full_name" name="full_name" value="{{ old('full_name', $patient->full_name) }}" required>
                @error('full_name')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="date_of_birth">تاريخ الميلاد</label>
                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($patient->date_of_birth)->format('Y-m-d')) }}">
                @error('date_of_birth')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="sex">الجنس</label>
                <select id="sex" name="sex">
                    <option value="male" @selected(old('sex', $patient->sex)==='male')>ذكر</option>
                    <option value="female" @selected(old('sex', $patient->sex)==='female')>أنثى</option>
                </select>
            </div>
            <div class="field">
                <label for="phone">رقم الهاتف</label>
                <input id="phone" name="phone" value="{{ old('phone', $patient->phone) }}" dir="ltr" style="text-align:start">
                @error('phone')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
            </div>
            <div class="field full">
                <label for="primary_doctor_id">الطبيب المعالج</label>
                <select id="primary_doctor_id" name="primary_doctor_id">
                    <option value="">—</option>
                    @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}" @selected(old('primary_doctor_id', $patient->primary_doctor_id)==$doctor->id)>{{ $doctor->user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('admin.patients.show', $patient) }}">إلغاء</a>
            <button class="btn" type="submit"><x-icon name="check" /> تحديث الملف</button>
        </div>
    </form>
</div>
@endsection
