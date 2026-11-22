@extends('layouts.admin')
@section('title', 'إنشاء ملف مريض')
@section('heading', 'إنشاء ملف مريض جديد')
@section('subheading', 'تسجيل بيانات المريض في النظام')
@section('content')
<div class="card" style="max-width:860px">
    <form method="post" action="{{ route('admin.patients.store') }}">@csrf
        <div class="form-section">
            <div class="form-section-title"><x-icon name="patients" /> البيانات الشخصية</div>
            <div class="form-grid">
                <div class="field">
                    <label for="full_name">الاسم الكامل <span class="req">*</span></label>
                    <input id="full_name" name="full_name" value="{{ old('full_name') }}" required>
                    @error('full_name')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="medical_file_number">الرقم الطبي <span class="muted small">(اختياري — يُولَّد تلقائياً)</span></label>
                    <input id="medical_file_number" name="medical_file_number" value="{{ old('medical_file_number') }}" dir="ltr" style="text-align:start">
                    @error('medical_file_number')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="date_of_birth">تاريخ الميلاد</label>
                    <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}">
                    @error('date_of_birth')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="sex">الجنس</label>
                    <select id="sex" name="sex">
                        <option value="">—</option>
                        <option value="male" @selected(old('sex')==='male')>ذكر</option>
                        <option value="female" @selected(old('sex')==='female')>أنثى</option>
                    </select>
                </div>
                <div class="field">
                    <label for="phone">رقم الهاتف</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" dir="ltr" style="text-align:start">
                    @error('phone')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="primary_doctor_id">الطبيب المعالج</label>
                    <select id="primary_doctor_id" name="primary_doctor_id">
                        <option value="">—</option>
                        @foreach($doctors as $doctor)<option value="{{ $doctor->id }}" @selected(old('primary_doctor_id')==$doctor->id)>{{ $doctor->user->name }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><x-icon name="key" /> حساب دخول المريض <span class="badge badge-neutral" style="font-weight:500">اختياري</span></div>
            <label class="check-line mb-2"><input type="checkbox" name="create_login" value="1" @checked(old('create_login'))> إنشاء حساب دخول للمريض</label>
            <div class="form-grid">
                <div class="field">
                    <label for="email">بريد الدخول</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" dir="ltr" style="text-align:start">
                    @error('email')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password">كلمة المرور</label>
                    <input id="password" type="password" name="password" autocomplete="new-password">
                    @error('password')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('admin.patients.index') }}">إلغاء</a>
            <button class="btn" type="submit"><x-icon name="check" /> حفظ الملف</button>
        </div>
    </form>
</div>
@endsection
