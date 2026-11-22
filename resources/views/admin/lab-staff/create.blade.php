@extends('layouts.admin')
@section('title', 'إضافة فني مختبر')
@section('heading', 'إضافة فني مختبر')
@section('subheading', 'إنشاء حساب فني مختبر في النظام')
@section('content')
<div class="card" style="max-width:720px">
    <form method="post" action="{{ route('admin.lab-staff.store') }}">@csrf
        <div class="form-grid">
            <div class="field">
                <label for="name">الاسم الكامل <span class="req">*</span></label>
                <input id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="field">
                <label for="email">البريد الإلكتروني <span class="req">*</span></label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required dir="ltr" style="text-align:start">
            </div>
            <div class="field">
                <label for="phone">رقم الهاتف</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" dir="ltr" style="text-align:start">
            </div>
            <div class="field">
                <label for="password">كلمة المرور <span class="req">*</span></label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
            </div>
            <div class="field">
                <label for="job_title">المسمى الوظيفي</label>
                <input id="job_title" name="job_title" value="{{ old('job_title') }}">
            </div>
            <div class="field">
                <label for="department_id">القسم</label>
                <select id="department_id" name="department_id">
                    <option value="">—</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id')==$d->id)>{{ $d->name_ar }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('admin.lab-staff.index') }}">إلغاء</a>
            <button class="btn" type="submit"><x-icon name="check" /> حفظ الفني</button>
        </div>
    </form>
</div>
@endsection
