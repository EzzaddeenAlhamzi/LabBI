@extends('layouts.admin')
@section('heading', 'إنشاء طلب فحص')
@section('subheading', 'طلب فحوصات مخبرية جديدة لأحد مرضاك')
@section('page_actions')
    <a class="btn btn-ghost" href="{{ route('doctor.orders.index') }}"><x-icon name="chevron-right" /> طلباتي</a>
@endsection
@section('content')
<div class="card">
    <div class="card-title"><x-icon name="clipboard" /> بيانات الطلب</div>
    <p class="card-sub">اختر المريض والفحوصات المطلوبة وأضف أي ملاحظات سريرية.</p>
    <form method="post" action="{{ route('doctor.orders.store') }}" class="form-grid" style="grid-template-columns:1fr;align-items:end">
        @csrf
        <div class="field">
            <label for="patient_id">المريض <span class="req">*</span></label>
            <select id="patient_id" name="patient_id" required>
                @foreach($patients as $patient)
                <option value="{{ $patient->id }}">{{ $patient->medical_file_number }} — {{ $patient->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>الفحوصات <span class="req">*</span></label>
            <div class="form-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.4rem;max-height:320px;overflow-y:auto;padding:.5rem;border:1px solid var(--line);border-radius:.6rem">
                @foreach($tests as $test)
                <label class="check-line">
                    <input type="checkbox" name="test_ids[]" value="{{ $test->id }}" style="width:auto">
                    <span><b dir="ltr">{{ $test->code }}</b> — {{ $test->name_ar }}</span>
                </label>
                @endforeach
            </div>
        </div>
        <div class="field">
            <label for="notes">ملاحظات</label>
            <textarea id="notes" name="notes" rows="3" placeholder="ملاحظات سريرية أو تعليمات للمختبر (اختياري)"></textarea>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit"><x-icon name="send" /> إرسال للمختبر</button>
        </div>
    </form>
</div>
@endsection
