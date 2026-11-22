@extends('layouts.admin')
@section('title', 'إنشاء طلب فحص')
@section('heading', 'إنشاء طلب فحص جديد')
@section('subheading', 'طلب فحص مخبري جديد يُرسل إلى المختبر')
@section('content')
<div class="card" style="max-width:860px">
    <form method="post" action="{{ route('admin.orders.store') }}">@csrf
        <div class="form-section">
            <div class="form-section-title"><x-icon name="patients" /> بيانات الطلب</div>
            <div class="form-grid">
                <div class="field">
                    <label for="patient_id">المريض <span class="req">*</span></label>
                    <select id="patient_id" name="patient_id" required>
                        @foreach($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->medical_file_number }} — {{ $patient->full_name }}</option>@endforeach
                    </select>
                    @error('patient_id')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="doctor_id">الطبيب <span class="req">*</span></label>
                    <select id="doctor_id" name="doctor_id" required>
                        @foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->user->name }}</option>@endforeach
                    </select>
                    @error('doctor_id')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="form-section">
            <div class="form-section-title"><x-icon name="test-tubes" /> الفحوصات المطلوبة</div>
            <div class="form-grid">
                @foreach($tests as $test)
                <div class="field" style="margin-bottom:.4rem">
                    <label class="check-line" style="font-weight:500">
                        <input type="checkbox" name="test_ids[]" value="{{ $test->id }}">
                        <span><code>{{ $test->code }}</code> — {{ $test->name_ar }}@if($test->unit) <span class="muted small">({{ $test->unit }})</span>@endif</span>
                    </label>
                </div>
                @endforeach
            </div>
            @error('test_ids')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
            @error('test_ids.*')<div class="error-text"><x-icon name="alert" /> {{ $message }}</div>@enderror
        </div>
        <div class="form-section">
            <div class="form-section-title"><x-icon name="file-text" /> ملاحظات</div>
            <div class="field">
                <textarea name="notes" rows="3" placeholder="ملاحظات سريرية أو تعليمات إضافية (اختياري)">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <a class="btn btn-secondary" href="{{ route('admin.orders.index') }}">إلغاء</a>
            <button class="btn" type="submit"><x-icon name="send" /> إنشاء وإرسال للمختبر</button>
        </div>
    </form>
</div>
@endsection
