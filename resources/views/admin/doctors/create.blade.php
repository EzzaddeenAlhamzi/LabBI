@extends('layouts.admin')
@section('title', 'إضافة طبيب')
@section('heading', 'إضافة طبيب جديد')
@section('subheading', 'إنشاء حساب طبيب في النظام')
@section('content')
<div class="card" style="max-width:720px">
    <form method="post" action="{{ route('admin.doctors.store') }}">@csrf
        <div class="form-section">
            <div class="form-section-title"><x-icon name="doctor" /> بيانات الطبيب</div>
            <div class="form-grid">
                <div class="field">
                    <label for="name">الاسم الكامل <span class="req">*</span></label>
                    <input id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="email">البريد الإلكتروني <span class="req">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required dir="ltr" style="text-align:start">
                    @error('email')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="phone">رقم الهاتف</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" dir="ltr" style="text-align:start">
                </div>
                <div class="field">
                    <label for="password">كلمة المرور <span class="req">*</span></label>
                    <input id="password" type="password" name="password" required autocomplete="new-password">
                    @error('password')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="license_number">رقم الترخيص</label>
                    <input id="license_number" name="license_number" value="{{ old('license_number') }}" dir="ltr" style="text-align:start">
                </div>
                <div class="field">
                    <label for="specialty">التخصص</label>
                    <input id="specialty" name="specialty" value="{{ old('specialty') }}">
                </div>
                <div class="field full">
                    <label for="department_id">القسم</label>
                    <select id="department_id" name="department_id">
                        <option value="">—</option>
                        @foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id')==$d->id)>{{ $d->name_ar }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('admin.doctors.index') }}">إلغاء</a>
            <button class="btn" type="submit"><x-icon name="check" /> حفظ الطبيب</button>
        </div>
    </form>
</div>
@endsection
