@extends('layouts.admin')
@section('title', 'إنشاء مستخدم')
@section('heading', 'إنشاء مستخدم جديد')
@section('subheading', 'حساب دخول جديد في النظام')
@section('content')
<div class="card" style="max-width:720px">
    <form method="post" action="{{ route('admin.users.store') }}">@csrf
        <div class="form-section">
            <div class="form-section-title"><x-icon name="user" /> المعلومات الأساسية</div>
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
                    @error('phone')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password">كلمة المرور <span class="req">*</span></label>
                    <input id="password" type="password" name="password" required autocomplete="new-password">
                    @error('password')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><x-icon name="shield" /> الوصول والصلاحيات</div>
            <div class="form-grid">
                <div class="field">
                    <label for="role_id">الدور <span class="req">*</span></label>
                    <select id="role_id" name="role_id" required>
                        @foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name_ar }} ({{ $role->code }})</option>@endforeach
                    </select>
                    @error('role_id')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field" style="display:flex;align-items:flex-end;padding-bottom:.4rem">
                    <label class="check-line" style="margin:0"><input type="checkbox" name="is_active" value="1" checked> حساب نشط</label>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">إلغاء</a>
            <button class="btn" type="submit"><x-icon name="check" /> حفظ المستخدم</button>
        </div>
    </form>
</div>
@endsection
