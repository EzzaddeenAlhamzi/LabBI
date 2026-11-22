@extends('layouts.admin')
@section('title', 'إضافة فحص')
@section('heading', 'إضافة فحص جديد')
@section('subheading', 'تسجيل فحص في الدليل المخبري')
@section('content')
<div class="card" style="max-width:720px">
    <form method="post" action="{{ route('admin.tests.store') }}">@csrf
        <div class="form-grid">
            <div class="field">
                <label for="code">رمز الفحص <span class="req">*</span></label>
                <input id="code" name="code" value="{{ old('code') }}" required dir="ltr" style="text-align:start">
                @error('code')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="name_ar">الاسم بالعربية <span class="req">*</span></label>
                <input id="name_ar" name="name_ar" value="{{ old('name_ar') }}" required>
                @error('name_ar')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="name_en">الاسم بالإنجليزية</label>
                <input id="name_en" name="name_en" value="{{ old('name_en') }}" dir="ltr" style="text-align:start">
            </div>
            <div class="field">
                <label for="unit">الوحدة</label>
                <input id="unit" name="unit" value="{{ old('unit') }}" placeholder="mg/dL, g/dL, U/L...">
            </div>
            <div class="field full">
                <label for="department_id">القسم</label>
                <select id="department_id" name="department_id">
                    <option value="">—</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id')==$d->id)>{{ $d->name_ar }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('admin.tests.index') }}">إلغاء</a>
            <button class="btn" type="submit"><x-icon name="check" /> حفظ الفحص</button>
        </div>
    </form>
</div>
@endsection
